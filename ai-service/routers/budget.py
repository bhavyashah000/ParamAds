"""
Budget Optimization Router
==========================
Cross-platform budget allocation and redistribution recommendations.
"""

from fastapi import APIRouter
from pydantic import BaseModel
from typing import List, Optional

router = APIRouter()


class CampaignPerformance(BaseModel):
    campaign_id: int
    platform: str  # meta, google
    current_budget: float
    spend: float
    revenue: float
    roas: float
    cpa: float
    conversions: int


class BudgetRequest(BaseModel):
    organization_id: int
    total_budget: float
    campaigns: List[CampaignPerformance]
    optimization_goal: str = "roas"  # roas, cpa, conversions


class BudgetRecommendation(BaseModel):
    campaign_id: int
    platform: str
    current_budget: float
    recommended_budget: float
    change_percent: float
    reason: str


class BudgetResponse(BaseModel):
    organization_id: int
    total_budget: float
    recommendations: List[BudgetRecommendation]
    expected_improvement: dict
    summary: str


@router.post("/optimize", response_model=BudgetResponse)
async def optimize_budget(request: BudgetRequest):
    """Generate budget allocation recommendations."""
    from services.budget_service import BudgetService

    service = BudgetService()
    result = service.optimize(
        organization_id=request.organization_id,
        total_budget=request.total_budget,
        campaigns=request.campaigns,
        optimization_goal=request.optimization_goal,
    )
    return result
