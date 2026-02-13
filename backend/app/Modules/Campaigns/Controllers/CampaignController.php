<?php

namespace App\Modules\Campaigns\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $campaignService
    ) {}

    /**
     * List campaigns for the organization.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Campaign::where('organization_id', $request->user()->organization_id)
            ->with('adAccount');

        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('ad_account_id')) {
            $query->where('ad_account_id', $request->ad_account_id);
        }

        $campaigns = $query->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json($campaigns);
    }

    /**
     * Get single campaign with metrics.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::where('organization_id', $request->user()->organization_id)
            ->with(['adAccount', 'adSets.ads', 'metrics' => function ($q) {
                $q->orderBy('date', 'desc')->limit(30);
            }])
            ->findOrFail($id);

        return response()->json(['data' => $campaign]);
    }

    /**
     * Pause a campaign.
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $this->campaignService->updateStatus($campaign, 'paused');

        return response()->json([
            'message' => 'Campaign paused.',
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Activate a campaign.
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $this->campaignService->updateStatus($campaign, 'active');

        return response()->json([
            'message' => 'Campaign activated.',
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Update campaign budget.
     */
    public function updateBudget(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'daily_budget' => 'required|numeric|min:1',
        ]);

        $campaign = Campaign::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $this->campaignService->updateBudget($campaign, $request->daily_budget);

        return response()->json([
            'message' => 'Campaign budget updated.',
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Bulk update campaigns.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_ids' => 'required|array',
            'campaign_ids.*' => 'integer',
            'action' => 'required|in:pause,activate',
        ]);

        $campaigns = Campaign::where('organization_id', $request->user()->organization_id)
            ->whereIn('id', $request->campaign_ids)
            ->get();

        foreach ($campaigns as $campaign) {
            $this->campaignService->updateStatus($campaign, $request->action === 'pause' ? 'paused' : 'active');
        }

        return response()->json([
            'message' => count($campaigns) . ' campaign(s) updated.',
        ]);
    }
}
