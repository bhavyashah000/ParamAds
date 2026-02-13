<?php

namespace App\Modules\CreativeIntelligence\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CreativeIntelligence\Models\Creative;
use App\Modules\CreativeIntelligence\Models\CreativeScore;
use App\Modules\CreativeIntelligence\Models\CreativeFatigue;
use App\Modules\CreativeIntelligence\Services\CreativeAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreativeIntelligenceController extends Controller
{
    public function __construct(
        private CreativeAnalysisService $analysisService
    ) {}

    /**
     * List creatives with scores.
     */
    public function index(Request $request): JsonResponse
    {
        $creatives = Creative::where('organization_id', $request->user()->organization_id)
            ->with('latestScore')
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json($creatives);
    }

    /**
     * Get creative details with full scoring history.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $creative = Creative::where('organization_id', $request->user()->organization_id)
            ->with(['scores' => function ($q) {
                $q->orderBy('date', 'desc')->limit(30);
            }, 'fatigueMetrics' => function ($q) {
                $q->orderBy('date', 'desc')->limit(14);
            }])
            ->findOrFail($id);

        return response()->json(['data' => $creative]);
    }

    /**
     * Get top performing creatives.
     */
    public function topPerformers(Request $request): JsonResponse
    {
        $data = $this->analysisService->getTopPerformers(
            $request->user()->organization_id,
            $request->get('limit', 10)
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get fatigued creatives.
     */
    public function fatigued(Request $request): JsonResponse
    {
        $data = $this->analysisService->getFatiguedCreatives(
            $request->user()->organization_id
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Trigger creative analysis for the organization.
     */
    public function analyze(Request $request): JsonResponse
    {
        $this->analysisService->analyzeOrganization($request->user()->organization_id);

        return response()->json(['message' => 'Creative analysis completed.']);
    }

    /**
     * Get creative performance comparison.
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'creative_ids' => 'required|array|min:2|max:10',
            'creative_ids.*' => 'integer',
        ]);

        $scores = CreativeScore::where('organization_id', $request->user()->organization_id)
            ->whereIn('creative_id', $request->creative_ids)
            ->where('date', now()->format('Y-m-d'))
            ->with('creative')
            ->get();

        return response()->json(['data' => $scores]);
    }

    /**
     * Get creative trend data.
     */
    public function trends(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'creative_type' => 'nullable|in:image,video,carousel',
        ]);

        $query = CreativeScore::where('organization_id', $request->user()->organization_id)
            ->whereBetween('date', [
                $request->get('date_from', now()->subDays(30)->format('Y-m-d')),
                $request->get('date_to', now()->format('Y-m-d')),
            ]);

        $trends = $query->select([
            'date',
            \DB::raw('AVG(overall_score) as avg_score'),
            \DB::raw('AVG(engagement_score) as avg_engagement'),
            \DB::raw('AVG(conversion_score) as avg_conversion'),
            \DB::raw('AVG(fatigue_score) as avg_fatigue'),
            \DB::raw('COUNT(*) as creative_count'),
        ])
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return response()->json(['data' => $trends]);
    }
}
