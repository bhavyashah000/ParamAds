<?php

namespace App\Modules\CrossPlatform\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CrossPlatform\Services\MetricNormalizationService;
use App\Modules\CrossPlatform\Services\UnifiedScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrossPlatformController extends Controller
{
    public function __construct(
        private MetricNormalizationService $normalizationService,
        private UnifiedScoringService $scoringService
    ) {}

    /**
     * Get unified cross-platform metrics.
     */
    public function unifiedMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $data = $this->normalizationService->getUnifiedMetrics(
            $request->user()->organization_id,
            $request->get('date_from', now()->subDays(7)->format('Y-m-d')),
            $request->get('date_to', now()->format('Y-m-d'))
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get daily unified metrics for charting.
     */
    public function dailyMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $data = $this->normalizationService->getDailyUnifiedMetrics(
            $request->user()->organization_id,
            $request->get('date_from', now()->subDays(30)->format('Y-m-d')),
            $request->get('date_to', now()->format('Y-m-d'))
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get campaign health scores.
     */
    public function campaignScores(Request $request): JsonResponse
    {
        $data = $this->scoringService->scoreOrganization(
            $request->user()->organization_id
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get budget reallocation recommendations.
     */
    public function budgetRecommendations(Request $request): JsonResponse
    {
        $data = $this->scoringService->getBudgetRecommendations(
            $request->user()->organization_id
        );

        return response()->json(['data' => $data]);
    }
}
