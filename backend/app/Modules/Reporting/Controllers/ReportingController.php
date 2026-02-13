<?php

namespace App\Modules\Reporting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reporting\Models\ScheduledReport;
use App\Modules\Reporting\Services\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    public function __construct(
        private ReportingService $reportingService
    ) {}

    /**
     * List scheduled reports.
     */
    public function index(Request $request): JsonResponse
    {
        $reports = ScheduledReport::where('organization_id', $request->user()->organization_id)
            ->with('creator:id,name')
            ->orderByDesc('updated_at')
            ->paginate($request->get('per_page', 25));

        return response()->json($reports);
    }

    /**
     * Create scheduled report.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:performance,creative,audience,budget,executive',
            'frequency' => 'required|in:daily,weekly,monthly',
            'format' => 'nullable|in:pdf,csv,xlsx',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'filters' => 'nullable|array',
            'sections' => 'nullable|array',
        ]);

        $report = $this->reportingService->createReport(
            $request->user()->organization_id,
            $request->user()->id,
            $request->all()
        );

        return response()->json(['data' => $report, 'message' => 'Scheduled report created.'], 201);
    }

    /**
     * Generate on-demand report.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'campaign_ids' => 'nullable|array',
        ]);

        $data = $this->reportingService->generateOnDemand(
            $request->user()->organization_id,
            $request->all()
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get report history.
     */
    public function history(Request $request, int $id): JsonResponse
    {
        $report = ScheduledReport::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $history = $report->history()->orderByDesc('created_at')->paginate(10);

        return response()->json($history);
    }

    /**
     * Delete scheduled report.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        ScheduledReport::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['message' => 'Report deleted.']);
    }
}
