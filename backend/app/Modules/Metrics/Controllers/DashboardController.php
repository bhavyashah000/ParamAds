<?php

namespace App\Modules\Metrics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Metrics\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Get KPI summary for the dashboard.
     */
    public function kpiSummary(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'platform' => 'nullable|in:meta,google,all',
        ]);

        $data = $this->dashboardService->getKPISummary(
            $request->user()->organization_id,
            $request->get('date_from', now()->subDays(7)->format('Y-m-d')),
            $request->get('date_to', now()->format('Y-m-d')),
            $request->get('platform', 'all')
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get campaign performance table.
     */
    public function campaignPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'platform' => 'nullable|in:meta,google,all',
            'sort_by' => 'nullable|string',
            'sort_dir' => 'nullable|in:asc,desc',
        ]);

        $data = $this->dashboardService->getCampaignPerformance(
            $request->user()->organization_id,
            $request->get('date_from', now()->subDays(7)->format('Y-m-d')),
            $request->get('date_to', now()->format('Y-m-d')),
            $request->get('platform', 'all'),
            $request->get('sort_by', 'spend'),
            $request->get('sort_dir', 'desc')
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get time-based comparison data.
     */
    public function timeComparison(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'compare_from' => 'required|date',
            'compare_to' => 'required|date',
        ]);

        $data = $this->dashboardService->getTimeComparison(
            $request->user()->organization_id,
            $request->date_from,
            $request->date_to,
            $request->compare_from,
            $request->compare_to
        );

        return response()->json(['data' => $data]);
    }
}
