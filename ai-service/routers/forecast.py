"""
Forecast Router
===============
Time-series forecasting for spend, conversions, ROAS, and budget optimization.
"""

from fastapi import APIRouter
from pydantic import BaseModel
from typing import List, Dict, Optional

router = APIRouter()


class ForecastRequest(BaseModel):
    campaign_id: int
    metric: str  # spend, conversions, roas, cpc, ctr
    historical_data: List[dict]  # [{date, value}, ...]
    forecast_days: int = 7


class BudgetForecastRequest(BaseModel):
    spend_data: List[Dict]
    revenue_data: List[Dict]
    target_roas: float
    budget_range: Dict


@router.post("/predict")
async def predict_metric(request: ForecastRequest):
    """Generate time-series forecast for a given metric."""
    from services.forecast_service import ForecastService

    service = ForecastService()
    return service.predict(
        campaign_id=request.campaign_id,
        metric=request.metric,
        historical_data=request.historical_data,
        forecast_days=request.forecast_days,
    )


@router.post("/batch-predict")
async def batch_predict(requests: List[ForecastRequest]):
    """Generate forecasts for multiple campaigns/metrics."""
    from services.forecast_service import ForecastService

    service = ForecastService()
    results = []
    for req in requests:
        result = service.predict(
            campaign_id=req.campaign_id,
            metric=req.metric,
            historical_data=req.historical_data,
            forecast_days=req.forecast_days,
        )
        results.append(result)
    return {"predictions": results}


@router.post("/budget")
async def forecast_budget(request: BudgetForecastRequest):
    """Generate budget optimization forecast."""
    from services.forecast_service import ForecastService

    service = ForecastService()
    return service.forecast_budget(
        spend_data=request.spend_data,
        revenue_data=request.revenue_data,
        target_roas=request.target_roas,
        budget_range=request.budget_range,
    )
