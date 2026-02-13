<?php

namespace App\Modules\Retargeting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Retargeting\Models\RetargetingFunnel;
use App\Modules\Retargeting\Services\RetargetingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RetargetingController extends Controller
{
    public function __construct(
        private RetargetingService $retargetingService
    ) {}

    /**
     * List retargeting funnels.
     */
    public function index(Request $request): JsonResponse
    {
        $funnels = RetargetingFunnel::where('organization_id', $request->user()->organization_id)
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json($funnels);
    }

    /**
     * Create retargeting funnel.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'platform' => 'required|in:meta,google',
            'stages' => 'required|array|min:2',
            'stages.*.name' => 'required|string',
            'stages.*.audience_type' => 'required|string',
            'stages.*.lookback_days' => 'required|integer|min:1|max:365',
            'pixel_id' => 'nullable|integer',
            'settings' => 'nullable|array',
        ]);

        $funnel = $this->retargetingService->createFunnel(
            $request->user()->organization_id,
            $request->all()
        );

        return response()->json([
            'message' => 'Retargeting funnel created.',
            'data' => $funnel,
        ], 201);
    }

    /**
     * Show funnel with performance.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $funnel = RetargetingFunnel::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $performance = $this->retargetingService->getFunnelPerformance($funnel);

        return response()->json([
            'data' => $funnel,
            'performance' => $performance,
        ]);
    }

    /**
     * Update funnel.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $funnel = RetargetingFunnel::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $funnel = $this->retargetingService->updateFunnel($funnel, $request->all());

        return response()->json([
            'message' => 'Funnel updated.',
            'data' => $funnel,
        ]);
    }

    /**
     * Delete funnel.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $funnel = RetargetingFunnel::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $funnel->delete();

        return response()->json(['message' => 'Funnel deleted.']);
    }

    /**
     * Get funnel templates.
     */
    public function templates(): JsonResponse
    {
        return response()->json([
            'data' => $this->retargetingService->getTemplates(),
        ]);
    }
}
