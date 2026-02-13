"""
Anomaly Detection Router
========================
Detect sudden CPC spikes, ROAS drops, CTR crashes.
Supports single-metric, multi-metric, and batch detection.
"""

from fastapi import APIRouter
from pydantic import BaseModel
from typing import List, Dict, Optional

router = APIRouter()


class AnomalyRequest(BaseModel):
    campaign_id: int
    metric: str
    data_points: List[dict]  # [{date, value}, ...]
    sensitivity: float = 2.0
    method: str = "ensemble"  # zscore, rolling, isolation_forest, ensemble


class MultiMetricAnomalyRequest(BaseModel):
    campaign_id: int
    metrics_data: Dict[str, List[dict]]  # {metric_name: [{date, value}]}
    sensitivity: float = 2.0


@router.post("/detect")
async def detect_anomalies(request: AnomalyRequest):
    """Detect anomalies in campaign metric data."""
    from services.anomaly_service import AnomalyService

    service = AnomalyService()
    return service.detect(
        campaign_id=request.campaign_id,
        metric=request.metric,
        data_points=request.data_points,
        sensitivity=request.sensitivity,
        method=request.method,
    )


@router.post("/detect-multi")
async def detect_multi_metric(request: MultiMetricAnomalyRequest):
    """Detect anomalies across multiple metrics with correlation analysis."""
    from services.anomaly_service import AnomalyService

    service = AnomalyService()
    return service.detect_multi_metric(
        campaign_id=request.campaign_id,
        metrics_data=request.metrics_data,
        sensitivity=request.sensitivity,
    )


@router.post("/batch-detect")
async def batch_detect(requests: List[AnomalyRequest]):
    """Detect anomalies across multiple campaigns."""
    from services.anomaly_service import AnomalyService

    service = AnomalyService()
    results = []
    for req in requests:
        result = service.detect(
            campaign_id=req.campaign_id,
            metric=req.metric,
            data_points=req.data_points,
            sensitivity=req.sensitivity,
            method=req.method,
        )
        results.append(result)
    return {"results": results}
