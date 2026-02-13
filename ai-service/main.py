"""
ParamAds AI Microservice
========================
FastAPI-based AI service for predictive analytics, anomaly detection,
budget optimization, and natural language insights.
"""

from fastapi import FastAPI, HTTPException, Depends, Security
from fastapi.security import APIKeyHeader
from fastapi.middleware.cors import CORSMiddleware
import os

app = FastAPI(
    title="ParamAds AI Service",
    description="Predictive AI layer for ParamAds performance marketing platform",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

API_KEY = os.getenv("AI_SERVICE_API_KEY", "paramads-ai-key")
api_key_header = APIKeyHeader(name="X-API-Key", auto_error=False)


async def verify_api_key(api_key: str = Security(api_key_header)):
    if api_key != API_KEY:
        raise HTTPException(status_code=403, detail="Invalid API key")
    return api_key


@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "paramads-ai"}


# Import routers
from routers import forecast, anomaly, budget, insights

app.include_router(forecast.router, prefix="/api/v1/forecast", tags=["Forecasting"])
app.include_router(anomaly.router, prefix="/api/v1/anomaly", tags=["Anomaly Detection"])
app.include_router(budget.router, prefix="/api/v1/budget", tags=["Budget Optimization"])
app.include_router(insights.router, prefix="/api/v1/insights", tags=["NL Insights"])

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)
