"""
Budget Optimization Service
===========================
Cross-platform budget allocation using efficiency scoring.
"""

import numpy as np
from typing import List
from datetime import datetime


class BudgetService:
    def optimize(
        self,
        organization_id: int,
        total_budget: float,
        campaigns: List,
        optimization_goal: str = "roas",
    ) -> dict:
        """Generate budget allocation recommendations based on campaign efficiency."""
        if not campaigns:
            return {
                "organization_id": organization_id,
                "total_budget": total_budget,
                "recommendations": [],
                "expected_improvement": {},
                "summary": "No campaigns provided for optimization.",
            }

        # Calculate efficiency scores
        scored_campaigns = []
        for c in campaigns:
            campaign = c if isinstance(c, dict) else c.dict()
            score = self._calculate_efficiency_score(campaign, optimization_goal)
            scored_campaigns.append({**campaign, "efficiency_score": score})

        # Sort by efficiency
        scored_campaigns.sort(key=lambda x: x["efficiency_score"], reverse=True)

        # Allocate budget proportionally to efficiency
        total_score = sum(c["efficiency_score"] for c in scored_campaigns)
        if total_score == 0:
            total_score = 1

        recommendations = []
        total_recommended = 0

        for c in scored_campaigns:
            proportion = c["efficiency_score"] / total_score
            recommended = round(total_budget * proportion, 2)
            current = c["current_budget"]
            change_pct = ((recommended - current) / current * 100) if current > 0 else 0

            reason = self._generate_reason(c, optimization_goal, change_pct)

            recommendations.append({
                "campaign_id": c["campaign_id"],
                "platform": c["platform"],
                "current_budget": current,
                "recommended_budget": recommended,
                "change_percent": round(change_pct, 1),
                "reason": reason,
            })
            total_recommended += recommended

        # Calculate expected improvement
        current_weighted_roas = self._weighted_metric(scored_campaigns, "roas", "current_budget")
        expected_weighted_roas = self._weighted_metric(
            scored_campaigns, "roas", "efficiency_score"
        )

        expected_improvement = {
            "metric": optimization_goal,
            "current_weighted_value": round(current_weighted_roas, 2),
            "expected_weighted_value": round(expected_weighted_roas, 2),
            "improvement_percent": round(
                ((expected_weighted_roas - current_weighted_roas) / current_weighted_roas * 100)
                if current_weighted_roas > 0
                else 0,
                1,
            ),
        }

        # Generate summary
        increases = [r for r in recommendations if r["change_percent"] > 5]
        decreases = [r for r in recommendations if r["change_percent"] < -5]
        summary_parts = [
            f"Budget optimization for {len(campaigns)} campaigns (${total_budget:,.2f} total)."
        ]
        if increases:
            platforms = set(r["platform"] for r in increases)
            summary_parts.append(f"Increase budget for {len(increases)} campaign(s) on {', '.join(platforms)}.")
        if decreases:
            platforms = set(r["platform"] for r in decreases)
            summary_parts.append(f"Decrease budget for {len(decreases)} campaign(s) on {', '.join(platforms)}.")

        return {
            "organization_id": organization_id,
            "total_budget": total_budget,
            "recommendations": recommendations,
            "expected_improvement": expected_improvement,
            "summary": " ".join(summary_parts),
        }

    def _calculate_efficiency_score(self, campaign: dict, goal: str) -> float:
        """Calculate efficiency score based on optimization goal."""
        if goal == "roas":
            return max(0, campaign.get("roas", 0))
        elif goal == "cpa":
            cpa = campaign.get("cpa", float("inf"))
            return max(0, 1 / cpa) if cpa > 0 else 0
        elif goal == "conversions":
            spend = campaign.get("spend", 1)
            conversions = campaign.get("conversions", 0)
            return conversions / spend if spend > 0 else 0
        return 0

    def _generate_reason(self, campaign: dict, goal: str, change_pct: float) -> str:
        if abs(change_pct) < 5:
            return f"Maintain current budget. {goal.upper()} is at {campaign.get(goal, 'N/A')}."
        elif change_pct > 0:
            return (
                f"Increase budget by {abs(change_pct):.1f}%. "
                f"Strong {goal.upper()} performance ({campaign.get(goal, 'N/A')}) "
                f"on {campaign['platform']}."
            )
        else:
            return (
                f"Decrease budget by {abs(change_pct):.1f}%. "
                f"Below-average {goal.upper()} ({campaign.get(goal, 'N/A')}) "
                f"on {campaign['platform']}."
            )

    def _weighted_metric(self, campaigns, metric_key, weight_key):
        total_weight = sum(c.get(weight_key, 0) for c in campaigns)
        if total_weight == 0:
            return 0
        return sum(c.get(metric_key, 0) * c.get(weight_key, 0) for c in campaigns) / total_weight
