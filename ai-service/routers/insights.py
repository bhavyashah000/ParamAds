"""
Natural Language Insights Router
=================================
Generate human-readable performance insights, action suggestions, and Q&A.
"""

from fastapi import APIRouter
from pydantic import BaseModel
from typing import List, Dict, Optional

router = APIRouter()


class InsightRequest(BaseModel):
    organization_id: int
    campaign_data: List[dict]
    time_range: str = "7d"
    include_recommendations: bool = True


class AskRequest(BaseModel):
    question: str
    context_data: Dict


class DeepDiveRequest(BaseModel):
    campaign: dict
    metrics_history: List[dict]


@router.post("/generate")
async def generate_insights(request: InsightRequest):
    """Generate natural language insights from campaign data."""
    from services.insight_service import InsightService

    service = InsightService()
    return service.generate(
        organization_id=request.organization_id,
        campaign_data=request.campaign_data,
        time_range=request.time_range,
        include_recommendations=request.include_recommendations,
    )


@router.post("/ask")
async def ask_question(request: AskRequest):
    """Answer a natural language question about ad performance."""
    from services.insight_service import InsightService

    service = InsightService()
    return service.ask_question(
        question=request.question,
        context_data=request.context_data,
    )


@router.post("/deep-dive")
async def campaign_deep_dive(request: DeepDiveRequest):
    """Generate deep-dive analysis for a single campaign."""
    from services.insight_service import InsightService

    service = InsightService()
    return service.generate_campaign_deep_dive(
        campaign=request.campaign,
        metrics_history=request.metrics_history,
    )
