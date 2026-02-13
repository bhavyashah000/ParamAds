"""
Anomaly Detection Service
=========================
Detect sudden CPC spikes, ROAS drops, CTR crashes using statistical methods.
Supports Z-score, rolling window, Isolation Forest, and multi-metric correlation.
"""

import numpy as np
from typing import List, Dict
from datetime import datetime
import logging

logger = logging.getLogger(__name__)


class AnomalyService:
    def detect(
        self,
        campaign_id: int,
        metric: str,
        data_points: List[dict],
        sensitivity: float = 2.0,
        method: str = "ensemble",
    ) -> dict:
        """Detect anomalies using multiple methods."""
        if len(data_points) < 5:
            return {
                "campaign_id": campaign_id,
                "metric": metric,
                "anomalies": [],
                "is_anomalous": False,
                "summary": "Insufficient data for anomaly detection (minimum 5 data points required).",
            }

        values = np.array([float(d["value"]) for d in data_points])
        dates = [d["date"] for d in data_points]

        anomalies = []

        # Z-score based detection
        mean = np.mean(values)
        std = np.std(values)

        if std == 0:
            return {
                "campaign_id": campaign_id,
                "metric": metric,
                "anomalies": [],
                "is_anomalous": False,
                "summary": f"No variance detected in {metric} data.",
            }

        # Method 1: Z-score
        if method in ("zscore", "ensemble"):
            for i, (date, value) in enumerate(zip(dates, values)):
                z_score = abs(value - mean) / std
                if z_score > sensitivity:
                    severity = "critical" if z_score > sensitivity * 1.5 else "warning"
                    direction = "spike" if value > mean else "drop"
                    anomalies.append({
                        "date": date,
                        "value": round(float(value), 4),
                        "expected_value": round(float(mean), 4),
                        "deviation": round(float(z_score), 2),
                        "deviation_percent": round(float((value - mean) / max(1, abs(mean)) * 100), 1),
                        "severity": severity,
                        "direction": direction,
                        "method": "zscore",
                    })

        # Method 2: Rolling window detection
        if method in ("rolling", "ensemble") and len(values) >= 7:
            window = min(7, len(values) // 2)
            for i in range(window, len(values)):
                window_values = values[max(0, i - window):i]
                ma = np.mean(window_values)
                ma_std = np.std(window_values)
                if ma_std > 0:
                    deviation = abs(values[i] - ma) / ma_std
                    if deviation > sensitivity:
                        anomalies.append({
                            "date": dates[i],
                            "value": round(float(values[i]), 4),
                            "expected_value": round(float(ma), 4),
                            "deviation": round(float(deviation), 2),
                            "deviation_percent": round(float((values[i] - ma) / max(1, abs(ma)) * 100), 1),
                            "severity": "critical" if deviation > sensitivity * 1.5 else "warning",
                            "direction": "spike" if values[i] > ma else "drop",
                            "method": "rolling_window",
                        })

        # Method 3: Isolation Forest
        if method in ("isolation_forest", "ensemble") and len(values) >= 10:
            try:
                from sklearn.ensemble import IsolationForest
                contamination = max(0.01, min(0.2, 1.0 / len(values) * 3))
                X = values.reshape(-1, 1)
                model = IsolationForest(contamination=contamination, random_state=42)
                predictions = model.fit_predict(X)
                scores = model.decision_function(X)

                for i, (pred, score) in enumerate(zip(predictions, scores)):
                    if pred == -1:
                        anomalies.append({
                            "date": dates[i],
                            "value": round(float(values[i]), 4),
                            "expected_value": round(float(mean), 4),
                            "deviation": round(float(abs(score)), 2),
                            "deviation_percent": round(float((values[i] - mean) / max(1, abs(mean)) * 100), 1),
                            "severity": "warning",
                            "direction": "spike" if values[i] > mean else "drop",
                            "method": "isolation_forest",
                        })
            except Exception as e:
                logger.warning(f"Isolation Forest failed: {e}")

        # Recent trend change detection
        if len(values) >= 7:
            recent = values[-3:]
            historical = values[:-3]
            recent_mean = np.mean(recent)
            hist_mean = np.mean(historical)
            hist_std = np.std(historical)

            if hist_std > 0:
                change_z = abs(recent_mean - hist_mean) / hist_std
                if change_z > sensitivity:
                    pct_change = ((recent_mean - hist_mean) / hist_mean) * 100
                    direction = "increasing" if recent_mean > hist_mean else "decreasing"
                    anomalies.append({
                        "date": dates[-1],
                        "value": round(float(recent_mean), 4),
                        "expected_value": round(float(hist_mean), 4),
                        "deviation": round(float(change_z), 2),
                        "deviation_percent": round(float(pct_change), 1),
                        "severity": "warning",
                        "direction": f"trend_{direction}",
                        "method": "trend_detection",
                    })

        # Deduplicate by date, keeping highest deviation
        anomalies = self._deduplicate_anomalies(anomalies)

        is_anomalous = len(anomalies) > 0
        summary = self._generate_summary(campaign_id, metric, anomalies, values)

        return {
            "campaign_id": campaign_id,
            "metric": metric,
            "anomalies": anomalies,
            "is_anomalous": is_anomalous,
            "anomaly_count": len(anomalies),
            "summary": summary,
            "statistics": {
                "mean": round(float(mean), 4),
                "std": round(float(std), 4),
                "min": round(float(np.min(values)), 4),
                "max": round(float(np.max(values)), 4),
                "median": round(float(np.median(values)), 4),
            },
        }

    def detect_multi_metric(
        self,
        campaign_id: int,
        metrics_data: Dict[str, List[dict]],
        sensitivity: float = 2.0,
    ) -> dict:
        """
        Detect anomalies across multiple metrics and find correlations.
        """
        results = {}
        all_anomaly_dates = {}

        for metric_name, data in metrics_data.items():
            result = self.detect(campaign_id, metric_name, data, sensitivity)
            results[metric_name] = result

            for anomaly in result.get("anomalies", []):
                date = anomaly["date"]
                if date not in all_anomaly_dates:
                    all_anomaly_dates[date] = []
                all_anomaly_dates[date].append({
                    "metric": metric_name,
                    "severity": anomaly["severity"],
                    "direction": anomaly["direction"],
                    "deviation": anomaly["deviation"],
                })

        # Find correlated anomalies
        correlated = []
        for date, metrics in all_anomaly_dates.items():
            if len(metrics) >= 2:
                correlated.append({
                    "date": date,
                    "affected_metrics": metrics,
                    "metric_count": len(metrics),
                    "likely_systemic": len(metrics) >= len(metrics_data) * 0.5,
                })

        correlated.sort(key=lambda x: x["metric_count"], reverse=True)

        return {
            "campaign_id": campaign_id,
            "per_metric": results,
            "correlated_anomalies": correlated,
            "systemic_issues": [c for c in correlated if c["likely_systemic"]],
            "total_anomalies": sum(r["anomaly_count"] for r in results.values()),
        }

    def _deduplicate_anomalies(self, anomalies: List[Dict]) -> List[Dict]:
        """Keep highest deviation anomaly per date."""
        by_date = {}
        for a in anomalies:
            date = a["date"]
            if date not in by_date or a["deviation"] > by_date[date]["deviation"]:
                # Preserve detected methods
                methods = [a["method"]]
                if date in by_date:
                    existing_method = by_date[date].get("detected_by", [by_date[date].get("method", "")])
                    methods = list(set(existing_method + methods))
                a["detected_by"] = methods
                by_date[date] = a

        return list(by_date.values())

    def _generate_summary(self, campaign_id, metric, anomalies, values):
        if not anomalies:
            return f"Campaign {campaign_id}: {metric} is within normal range."

        critical = [a for a in anomalies if a["severity"] == "critical"]
        warnings = [a for a in anomalies if a["severity"] == "warning"]

        parts = [f"Campaign {campaign_id}: {len(anomalies)} anomaly(ies) detected in {metric}."]
        if critical:
            parts.append(f"{len(critical)} critical anomaly(ies).")
        if warnings:
            parts.append(f"{len(warnings)} warning(s).")

        latest = anomalies[-1]
        parts.append(
            f"Latest: {latest['direction']} on {latest['date']} "
            f"(value: {latest['value']}, expected: {latest['expected_value']})."
        )

        return " ".join(parts)
