<?php

namespace App\Modules\AudienceIntelligence\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AudienceIntelligence\Models\Audience;
use App\Modules\AudienceIntelligence\Models\Pixel;
use App\Modules\AudienceIntelligence\Services\AudienceIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AudienceIntelligenceController extends Controller
{
    public function __construct(
        private AudienceIntelligenceService $audienceService
    ) {}

    /**
     * List audiences.
     */
    public function index(Request $request): JsonResponse
    {
        $audiences = Audience::where('organization_id', $request->user()->organization_id)
            ->when($request->platform, fn($q, $p) => $q->where('platform', $p))
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->orderBy('size', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json($audiences);
    }

    /**
     * Get audience insights.
     */
    public function insights(Request $request): JsonResponse
    {
        $data = $this->audienceService->getAudienceInsights(
            $request->user()->organization_id
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Sync audiences from platform.
     */
    public function sync(Request $request, int $adAccountId): JsonResponse
    {
        $adAccount = \App\Modules\AdAccounts\Models\AdAccount::where('organization_id', $request->user()->organization_id)
            ->findOrFail($adAccountId);

        $this->audienceService->syncAudiences($adAccount);

        return response()->json(['message' => 'Audience sync completed.']);
    }

    /**
     * Calculate overlap between audiences.
     */
    public function overlap(Request $request): JsonResponse
    {
        $request->validate([
            'audience_a_id' => 'required|integer',
            'audience_b_id' => 'required|integer',
        ]);

        $audienceA = Audience::where('organization_id', $request->user()->organization_id)
            ->findOrFail($request->audience_a_id);
        $audienceB = Audience::where('organization_id', $request->user()->organization_id)
            ->findOrFail($request->audience_b_id);

        $overlap = $this->audienceService->calculateOverlap($audienceA, $audienceB);

        return response()->json(['data' => $overlap]);
    }

    /**
     * List pixels.
     */
    public function pixels(Request $request): JsonResponse
    {
        $pixels = Pixel::where('organization_id', $request->user()->organization_id)
            ->with('adAccount')
            ->get();

        return response()->json(['data' => $pixels]);
    }

    /**
     * Sync pixels.
     */
    public function syncPixels(Request $request, int $adAccountId): JsonResponse
    {
        $adAccount = \App\Modules\AdAccounts\Models\AdAccount::where('organization_id', $request->user()->organization_id)
            ->findOrFail($adAccountId);

        $this->audienceService->syncPixels($adAccount);

        return response()->json(['message' => 'Pixel sync completed.']);
    }
}
