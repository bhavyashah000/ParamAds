<?php

namespace App\Modules\AI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AI\Services\AIBridgeService;
use App\Modules\Metrics\Models\CampaignMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function __construct(
        private AIBridgeService $aiService
    ) {}

    /**
     * Get metric forecast for a campaign.
     */
    public function forecast(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_id' => 'required|integer',
            'metric' => 'required|string|in:spend,impressions,clicks,conversions,ctr,cpc,cpa,roas',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $metrics = CampaignMetric::where('campaign_id', $request->campaign_id)
            ->where('date', '>=', now()->subDays(90))
            ->orderBy('date')
            ->get()
            ->map(fn($m) => ['date' => $m->date->format('Y-m-d'), 'value' => $m->{$request->metric}])
            ->toArray();

        $result = $this->aiService->forecast(
            $request->campaign_id,
            $request->metric,
            $metrics,
            $request->get('days', 7)
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Detect anomalies for a campaign.
     */
    public function anomalies(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_id' => 'required|integer',
            'metric' => 'required|string',
            'sensitivity' => 'nullable|numeric|min:1|max:5',
        ]);

        $metrics = CampaignMetric::where('campaign_id', $request->campaign_id)
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date')
            ->get()
            ->map(fn($m) => ['date' => $m->date->format('Y-m-d'), 'value' => $m->{$request->metric}])
            ->toArray();

        $result = $this->aiService->detectAnomalies(
            $request->campaign_id,
            $request->metric,
            $metrics,
            $request->get('sensitivity', 2.0)
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Generate NL insights.
     */
    public function insights(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        // Gather campaign data for the organization
        $campaigns = \App\Modules\Campaigns\Models\Campaign::where('organization_id', $orgId)
            ->with('latestMetrics')
            ->get()
            ->map(function ($campaign) {
                $m = $campaign->latestMetrics;
                return [
                    'campaign_id' => $campaign->id,
                    'name' => $campaign->name,
                    'platform' => $campaign->platform,
                    'status' => $campaign->status,
                    'daily_budget' => $campaign->daily_budget,
                    'spend' => $m?->spend ?? 0,
                    'impressions' => $m?->impressions ?? 0,
                    'clicks' => $m?->clicks ?? 0,
                    'conversions' => $m?->conversions ?? 0,
                    'revenue' => $m?->revenue ?? 0,
                    'roas' => $m?->roas ?? 0,
                    'ctr' => $m?->ctr ?? 0,
                    'cpc' => $m?->cpc ?? 0,
                    'cpa' => $m?->cpa ?? 0,
                ];
            })
            ->toArray();

        $result = $this->aiService->generateInsights($orgId, $campaigns);

        return response()->json(['data' => $result]);
    }

    /**
     * Ask a natural language question.
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $orgId = $request->user()->organization_id;

        // Build context data
        $context = [
            'total_spend' => CampaignMetric::whereHas('campaign', fn($q) => $q->where('organization_id', $orgId))
                ->where('date', '>=', now()->subDays(7))
                ->sum('spend'),
            'total_revenue' => CampaignMetric::whereHas('campaign', fn($q) => $q->where('organization_id', $orgId))
                ->where('date', '>=', now()->subDays(7))
                ->sum('revenue'),
            'total_conversions' => CampaignMetric::whereHas('campaign', fn($q) => $q->where('organization_id', $orgId))
                ->where('date', '>=', now()->subDays(7))
                ->sum('conversions'),
        ];

        $result = $this->aiService->askQuestion($request->question, $context);

        return response()->json(['data' => $result]);
    }

    /**
     * Health check for AI service.
     */
    public function health(): JsonResponse
    {
        $healthy = $this->aiService->healthCheck();

        return response()->json([
            'ai_service' => $healthy ? 'connected' : 'unavailable',
        ], $healthy ? 200 : 503);
    }
}
