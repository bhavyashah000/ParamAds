"""
Forecast Service
================
Time-series forecasting using multiple models.
Falls back gracefully between Prophet, XGBoost, and simple statistical methods.
Includes budget optimization forecasting.
"""

import numpy as np
from datetime import datetime, timedelta
from typing import List, Dict, Optional
import logging

logger = logging.getLogger(__name__)


class ForecastService:
    def predict(
        self,
        campaign_id: int,
        metric: str,
        historical_data: List[dict],
        forecast_days: int = 7,
    ) -> dict:
        """Generate forecast using best available model."""
        if len(historical_data) < 3:
            return self._insufficient_data_response(campaign_id, metric, forecast_days)

        values = [float(d["value"]) for d in historical_data]
        dates = [d["date"] for d in historical_data]

        try:
            predictions = self._prophet_forecast(dates, values, forecast_days)
            model_used = "prophet"
        except Exception:
            try:
                predictions = self._xgboost_forecast(values, forecast_days)
                model_used = "xgboost"
            except Exception:
                predictions = self._statistical_forecast(values, forecast_days)
                model_used = "statistical"

        last_date = datetime.strptime(dates[-1], "%Y-%m-%d")
        result_predictions = []
        for i, pred in enumerate(predictions):
            pred_date = last_date + timedelta(days=i + 1)
            std_dev = np.std(values) * 0.5
            result_predictions.append({
                "date": pred_date.strftime("%Y-%m-%d"),
                "predicted_value": round(float(pred), 4),
                "lower_bound": round(float(max(0, pred - 1.96 * std_dev)), 4),
                "upper_bound": round(float(pred + 1.96 * std_dev), 4),
            })

        confidence = min(0.95, 0.5 + len(historical_data) * 0.01)

        # Trend analysis
        trend_direction = "stable"
        if len(values) >= 7:
            recent_avg = np.mean(values[-7:])
            older_avg = np.mean(values[-14:-7]) if len(values) >= 14 else np.mean(values[:len(values)//2])
            if recent_avg > older_avg * 1.05:
                trend_direction = "increasing"
            elif recent_avg < older_avg * 0.95:
                trend_direction = "decreasing"

        return {
            "campaign_id": campaign_id,
            "metric": metric,
            "predictions": result_predictions,
            "confidence": round(confidence, 2),
            "model_used": model_used,
            "summary": {
                "current_avg": round(float(np.mean(values[-7:])), 4),
                "forecasted_avg": round(float(np.mean([p["predicted_value"] for p in result_predictions])), 4),
                "trend_direction": trend_direction,
                "data_points": len(values),
            },
        }

    def forecast_budget(
        self,
        spend_data: List[Dict],
        revenue_data: List[Dict],
        target_roas: float,
        budget_range: Dict,
    ) -> Dict:
        """
        Forecast optimal budget allocation using diminishing returns model.
        """
        if len(spend_data) < 7:
            return {"error": "Insufficient data for budget forecasting", "min_required": 7}

        spends = np.array([float(d["value"]) for d in spend_data])
        revenues = np.array([float(d["value"]) for d in revenue_data[:len(spends)]])

        current_roas = float(np.sum(revenues)) / max(1, float(np.sum(spends)))
        avg_daily_spend = float(np.mean(spends[-7:]))
        avg_daily_revenue = float(np.mean(revenues[-7:]))

        min_budget = budget_range.get("min", avg_daily_spend * 0.5)
        max_budget = budget_range.get("max", avg_daily_spend * 2.0)

        # Log-linear diminishing returns model
        log_spends = np.log(np.maximum(spends, 1))
        try:
            coeffs = np.polyfit(log_spends, revenues, 1)
            a, b = coeffs[0], coeffs[1]
            # Marginal ROAS = a / spend; find where marginal ROAS = target_roas
            optimal = a / target_roas if target_roas > 0 else avg_daily_spend
        except Exception:
            optimal = avg_daily_spend

        optimal = max(min_budget, min(max_budget, optimal))

        # Project revenue at different spend levels
        scenarios = []
        for multiplier in [0.5, 0.75, 1.0, 1.25, 1.5, 2.0]:
            test_spend = avg_daily_spend * multiplier
            projected_revenue = a * np.log(max(1, test_spend)) + b if 'a' in dir() else test_spend * current_roas
            scenarios.append({
                "daily_budget": round(test_spend, 2),
                "projected_revenue": round(float(max(0, projected_revenue)), 2),
                "projected_roas": round(float(max(0, projected_revenue)) / max(1, test_spend), 2),
                "budget_change_percent": round((multiplier - 1) * 100, 1),
            })

        return {
            "current_daily_spend": round(avg_daily_spend, 2),
            "current_daily_revenue": round(avg_daily_revenue, 2),
            "current_roas": round(current_roas, 2),
            "target_roas": target_roas,
            "recommended_daily_budget": round(optimal, 2),
            "projected_daily_revenue": round(optimal * min(target_roas, current_roas * 1.1), 2),
            "budget_change_percent": round(((optimal - avg_daily_spend) / max(1, avg_daily_spend)) * 100, 1),
            "scenarios": scenarios,
            "confidence": "medium" if len(spend_data) >= 30 else "low",
        }

    def _prophet_forecast(self, dates, values, forecast_days):
        """Forecast using Facebook Prophet."""
        from prophet import Prophet
        import pandas as pd

        df = pd.DataFrame({"ds": pd.to_datetime(dates), "y": values})
        model = Prophet(daily_seasonality=True, yearly_seasonality=False)
        model.fit(df)
        future = model.make_future_dataframe(periods=forecast_days)
        forecast = model.predict(future)
        return forecast["yhat"].tail(forecast_days).values

    def _xgboost_forecast(self, values, forecast_days):
        """Forecast using XGBoost with lag features."""
        from xgboost import XGBRegressor

        lag = min(7, len(values) - 1)
        X, y = [], []
        for i in range(lag, len(values)):
            X.append(values[i - lag:i])
            y.append(values[i])

        X = np.array(X)
        y = np.array(y)

        model = XGBRegressor(n_estimators=100, max_depth=3, learning_rate=0.1)
        model.fit(X, y)

        predictions = []
        current = list(values[-lag:])
        for _ in range(forecast_days):
            pred = model.predict(np.array([current[-lag:]]))[0]
            predictions.append(max(0, pred))
            current.append(pred)

        return predictions

    def _statistical_forecast(self, values, forecast_days):
        """Simple moving average + trend forecast."""
        window = min(7, len(values))
        ma = np.mean(values[-window:])

        if len(values) >= 2:
            trend = (values[-1] - values[-window]) / window
        else:
            trend = 0

        predictions = []
        for i in range(forecast_days):
            pred = ma + trend * (i + 1)
            predictions.append(max(0, pred))

        return predictions

    def _insufficient_data_response(self, campaign_id, metric, forecast_days):
        return {
            "campaign_id": campaign_id,
            "metric": metric,
            "predictions": [],
            "confidence": 0.0,
            "model_used": "none",
            "summary": {"data_points": 0, "confidence": "none"},
        }
