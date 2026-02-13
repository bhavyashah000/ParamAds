"""
Natural Language Insight Service
================================
Generate human-readable performance insights and action suggestions.
Supports AI-powered summaries via OpenAI-compatible API and natural language Q&A.
"""

import os
import json
import numpy as np
from typing import List, Dict, Optional
from datetime import datetime
import logging

logger = logging.getLogger(__name__)


class InsightService:
    def __init__(self):
        self.api_key = os.getenv("OPENAI_API_KEY")

    def generate(
        self,
        organization_id: int,
        campaign_data: List[dict],
        time_range: str = "7d",
        include_recommendations: bool = True,
    ) -> dict:
        """Generate NL insights from campaign performance data."""
        insights = []

        for campaign in campaign_data:
            campaign_insights = self._analyze_campaign(campaign, time_range)
            insights.extend(campaign_insights)

        # Add cross-campaign insights
        if len(campaign_data) > 1:
            cross_insights = self._cross_campaign_analysis(campaign_data, time_range)
            insights.extend(cross_insights)

        if include_recommendations:
            recs = self._generate_recommendations(campaign_data, insights)
            insights.extend(recs)

        # Sort by severity
        severity_order = {"critical": 0, "warning": 1, "info": 2}
        insights.sort(key=lambda x: severity_order.get(x.get("severity", "info"), 3))

        summary = self._generate_summary(insights, campaign_data)

        # AI-powered executive summary
        ai_summary = None
        if self.api_key:
            ai_summary = self._generate_ai_summary(campaign_data, insights)

        return {
            "organization_id": organization_id,
            "insights": insights,
            "generated_at": datetime.utcnow().isoformat(),
            "summary": summary,
            "ai_summary": ai_summary,
        }

    def ask_question(self, question: str, context_data: Dict) -> Dict:
        """Answer a natural language question about ad performance."""
        if self.api_key:
            return self._ask_with_llm(question, context_data)
        return self._ask_rule_based(question, context_data)

    def generate_campaign_deep_dive(self, campaign: dict, metrics_history: List[dict]) -> Dict:
        """Generate deep-dive analysis for a single campaign."""
        insights = self._analyze_campaign(campaign, "7d")

        # Trend analysis
        if len(metrics_history) >= 7:
            trend = self._analyze_metric_trends(metrics_history)
            if trend:
                insights.append(trend)

        # Budget analysis
        budget_insight = self._analyze_budget_utilization(campaign, metrics_history)
        if budget_insight:
            insights.append(budget_insight)

        recommendations = self._generate_recommendations([campaign], insights)

        return {
            "campaign_id": campaign.get("campaign_id"),
            "campaign_name": campaign.get("name"),
            "insights": insights,
            "recommendations": recommendations,
            "generated_at": datetime.utcnow().isoformat(),
        }

    def _analyze_campaign(self, campaign: dict, time_range: str) -> List[dict]:
        """Analyze individual campaign performance."""
        insights = []
        cid = campaign.get("campaign_id", 0)
        name = campaign.get("name", f"Campaign {cid}")

        # ROAS analysis
        roas = campaign.get("roas", 0)
        prev_roas = campaign.get("prev_roas", roas)
        if roas < 1.0:
            insights.append({
                "type": "alert",
                "severity": "critical",
                "title": f"Low ROAS on {name}",
                "description": f"{name} has a ROAS of {roas:.2f}x, meaning you're losing money on ad spend. Immediate action recommended.",
                "affected_campaigns": [cid],
                "suggested_action": "Consider pausing this campaign or significantly reducing budget until creative/targeting is optimized.",
            })
        elif prev_roas > 0 and (roas - prev_roas) / prev_roas < -0.2:
            insights.append({
                "type": "trend",
                "severity": "warning",
                "title": f"ROAS declining on {name}",
                "description": f"{name} ROAS dropped from {prev_roas:.2f}x to {roas:.2f}x ({((roas-prev_roas)/prev_roas*100):.1f}% change).",
                "affected_campaigns": [cid],
                "suggested_action": "Review recent creative changes and audience targeting. Consider A/B testing new creatives.",
            })

        # CPC analysis
        cpc = campaign.get("cpc", 0)
        prev_cpc = campaign.get("prev_cpc", cpc)
        if prev_cpc > 0 and cpc > 0 and (cpc - prev_cpc) / prev_cpc > 0.3:
            insights.append({
                "type": "alert",
                "severity": "warning",
                "title": f"CPC spike on {name}",
                "description": f"Cost per click increased by {((cpc-prev_cpc)/prev_cpc*100):.1f}% from ${prev_cpc:.2f} to ${cpc:.2f}.",
                "affected_campaigns": [cid],
                "suggested_action": "Check for audience saturation or increased competition. Refresh creatives and expand targeting.",
            })

        # CTR analysis
        ctr = campaign.get("ctr", 0)
        if ctr < 0.5:
            insights.append({
                "type": "alert",
                "severity": "warning",
                "title": f"Low CTR on {name}",
                "description": f"{name} has a CTR of {ctr:.2f}%, which is below industry average. Creative or targeting may need improvement.",
                "affected_campaigns": [cid],
                "suggested_action": "Test new ad creatives, headlines, and CTAs. Consider narrowing audience targeting.",
            })

        # Spend efficiency
        spend = campaign.get("spend", 0)
        conversions = campaign.get("conversions", 0)
        if spend > 0 and conversions == 0:
            insights.append({
                "type": "alert",
                "severity": "critical",
                "title": f"No conversions on {name}",
                "description": f"{name} has spent ${spend:.2f} with zero conversions. This campaign needs immediate attention.",
                "affected_campaigns": [cid],
                "suggested_action": "Pause campaign and review conversion tracking, landing page, and targeting setup.",
            })

        return insights

    def _cross_campaign_analysis(self, campaigns: List[dict], time_range: str) -> List[dict]:
        """Analyze performance across campaigns."""
        insights = []

        meta_campaigns = [c for c in campaigns if c.get("platform") == "meta"]
        google_campaigns = [c for c in campaigns if c.get("platform") == "google"]

        if meta_campaigns and google_campaigns:
            meta_roas = np.mean([c.get("roas", 0) for c in meta_campaigns])
            google_roas = np.mean([c.get("roas", 0) for c in google_campaigns])

            if abs(meta_roas - google_roas) > 0.5:
                better = "Meta" if meta_roas > google_roas else "Google"
                worse = "Google" if meta_roas > google_roas else "Meta"
                diff = abs(meta_roas - google_roas)

                insights.append({
                    "type": "trend",
                    "severity": "info",
                    "title": f"{better} outperforming {worse}",
                    "description": f"{better} campaigns average {max(meta_roas, google_roas):.2f}x ROAS vs {min(meta_roas, google_roas):.2f}x on {worse} (difference: {diff:.2f}x).",
                    "affected_campaigns": [c.get("campaign_id", 0) for c in campaigns],
                    "suggested_action": f"Consider shifting budget from {worse} to {better} to improve overall ROAS.",
                })

        return insights

    def _analyze_metric_trends(self, metrics: List[dict]) -> Optional[dict]:
        """Analyze trends in metric history."""
        recent = metrics[-7:]
        older = metrics[-14:-7] if len(metrics) >= 14 else metrics[:len(metrics)//2]

        recent_spend = sum(m.get("spend", 0) for m in recent)
        older_spend = sum(m.get("spend", 0) for m in older) or 1
        spend_change = ((recent_spend - older_spend) / older_spend) * 100

        recent_conv = sum(m.get("conversions", 0) for m in recent)
        older_conv = sum(m.get("conversions", 0) for m in older) or 1
        conv_change = ((recent_conv - older_conv) / older_conv) * 100

        direction = "improving" if conv_change > 5 else "declining" if conv_change < -5 else "stable"

        return {
            "type": "trend",
            "severity": "high" if abs(conv_change) > 20 else "info",
            "title": f"Performance {direction.title()}",
            "description": (
                f"Over the last 7 days, conversions changed by {conv_change:+.1f}% "
                f"while spend changed by {spend_change:+.1f}%. Performance is {direction}."
            ),
        }

    def _analyze_budget_utilization(self, campaign: dict, metrics: List[dict]) -> Optional[dict]:
        """Analyze budget utilization."""
        daily_budget = campaign.get("daily_budget", 0)
        if not daily_budget or not metrics:
            return None

        recent_spend = np.mean([m.get("spend", 0) for m in metrics[-7:]])
        utilization = (recent_spend / daily_budget) * 100

        if utilization < 70:
            return {
                "type": "budget",
                "severity": "warning",
                "title": "Budget Underutilization",
                "description": f"Only {utilization:.0f}% of daily budget (${daily_budget:.2f}) is being spent. Consider expanding targeting.",
            }
        elif utilization > 95:
            return {
                "type": "budget",
                "severity": "info",
                "title": "Budget Capped",
                "description": f"Campaign is spending {utilization:.0f}% of budget. Consider increasing budget to capture more conversions.",
            }
        return None

    def _generate_recommendations(self, campaigns: List[dict], existing_insights: List[dict]) -> List[dict]:
        """Generate actionable recommendations."""
        recommendations = []

        if campaigns:
            top = max(campaigns, key=lambda c: c.get("roas", 0))
            if top.get("roas", 0) > 2.0:
                recommendations.append({
                    "type": "recommendation",
                    "severity": "info",
                    "title": f"Scale top performer: {top.get('name', 'Campaign ' + str(top.get('campaign_id', 0)))}",
                    "description": f"This campaign has a strong ROAS of {top.get('roas', 0):.2f}x. Consider increasing its budget by 20-30% to capture more conversions.",
                    "affected_campaigns": [top.get("campaign_id", 0)],
                    "suggested_action": "Increase daily budget by 20% and monitor for 3-5 days.",
                })

        return recommendations

    def _generate_summary(self, insights: List[dict], campaigns: List[dict]) -> str:
        """Generate overall summary."""
        critical = len([i for i in insights if i.get("severity") == "critical"])
        warnings = len([i for i in insights if i.get("severity") == "warning"])
        total_spend = sum(c.get("spend", 0) for c in campaigns)
        avg_roas = np.mean([c.get("roas", 0) for c in campaigns]) if campaigns else 0

        parts = [f"Analysis of {len(campaigns)} campaign(s) with ${total_spend:,.2f} total spend."]
        parts.append(f"Average ROAS: {avg_roas:.2f}x.")

        if critical:
            parts.append(f"{critical} critical issue(s) require immediate attention.")
        if warnings:
            parts.append(f"{warnings} warning(s) to review.")
        if not critical and not warnings:
            parts.append("All campaigns performing within normal parameters.")

        return " ".join(parts)

    def _generate_ai_summary(self, campaigns: List[dict], insights: List[dict]) -> Optional[str]:
        """Generate AI-powered executive summary."""
        try:
            from openai import OpenAI
            client = OpenAI()

            context = json.dumps({
                "campaign_count": len(campaigns),
                "total_spend": sum(c.get("spend", 0) for c in campaigns),
                "avg_roas": float(np.mean([c.get("roas", 0) for c in campaigns])) if campaigns else 0,
                "critical_issues": len([i for i in insights if i.get("severity") == "critical"]),
                "top_insights": [{"title": i["title"], "severity": i["severity"]} for i in insights[:5]],
            })

            response = client.chat.completions.create(
                model="gpt-4.1-nano",
                messages=[
                    {"role": "system", "content": "You are an expert digital marketing analyst. Provide a concise 2-3 sentence executive summary. Be specific and actionable."},
                    {"role": "user", "content": f"Summarize this ad portfolio performance:\n{context}"},
                ],
                max_tokens=200,
                temperature=0.3,
            )

            return response.choices[0].message.content.strip()
        except Exception as e:
            logger.warning(f"AI summary generation failed: {e}")
            return None

    def _ask_with_llm(self, question: str, context_data: Dict) -> Dict:
        """Answer question using LLM."""
        try:
            from openai import OpenAI
            client = OpenAI()

            context = json.dumps(context_data, indent=2, default=str)

            response = client.chat.completions.create(
                model="gpt-4.1-nano",
                messages=[
                    {"role": "system", "content": "You are an expert digital marketing analyst. Answer questions about ad performance data concisely and accurately. Use specific numbers."},
                    {"role": "user", "content": f"Data:\n{context}\n\nQuestion: {question}"},
                ],
                max_tokens=300,
                temperature=0.3,
            )

            return {
                "question": question,
                "answer": response.choices[0].message.content.strip(),
                "source": "ai",
            }
        except Exception as e:
            return self._ask_rule_based(question, context_data)

    def _ask_rule_based(self, question: str, context_data: Dict) -> Dict:
        """Simple rule-based question answering fallback."""
        q = question.lower()
        answer = "I don't have enough information to answer that question."

        if "roas" in q or "return" in q:
            spend = context_data.get("total_spend", 0)
            revenue = context_data.get("total_revenue", 0)
            roas = revenue / max(1, spend)
            answer = f"Your current ROAS is {roas:.2f}x (${revenue:,.2f} revenue / ${spend:,.2f} spend)."
        elif "spend" in q or "budget" in q:
            spend = context_data.get("total_spend", 0)
            answer = f"Total spend is ${spend:,.2f}."
        elif "conversion" in q:
            conv = context_data.get("total_conversions", 0)
            answer = f"Total conversions: {conv:,}."

        return {"question": question, "answer": answer, "source": "rule_based"}
