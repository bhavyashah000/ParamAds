<?php

namespace App\Modules\Automation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Automation\Models\AutomationRule;
use App\Modules\Automation\Models\AutomationLog;
use App\Modules\Automation\Services\AutomationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function __construct(
        private AutomationEngine $engine
    ) {}

    /**
     * List automation rules.
     */
    public function index(Request $request): JsonResponse
    {
        $rules = AutomationRule::where('organization_id', $request->user()->organization_id)
            ->withCount('logs')
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json($rules);
    }

    /**
     * Create automation rule.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope' => 'required|in:campaign,adset,ad',
            'platform' => 'nullable|in:meta,google,all',
            'conditions' => 'required|array|min:1',
            'conditions.*.metric' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required|numeric',
            'condition_logic' => 'required|in:AND,OR',
            'actions' => 'required|array|min:1',
            'actions.*.type' => 'required|string',
            'time_window' => 'required|string|in:1h,6h,12h,24h,3d,7d,14d,30d',
            'check_interval_minutes' => 'required|integer|min:15',
            'cooldown_minutes' => 'required|integer|min:60',
            'target_ids' => 'nullable|array',
            'apply_to_all' => 'boolean',
        ]);

        $rule = AutomationRule::create([
            'organization_id' => $request->user()->organization_id,
            ...$request->only([
                'name', 'description', 'scope', 'platform', 'conditions',
                'condition_logic', 'actions', 'time_window', 'check_interval_minutes',
                'cooldown_minutes', 'target_ids', 'apply_to_all',
            ]),
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Automation rule created.',
            'data' => $rule,
        ], 201);
    }

    /**
     * Show automation rule.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $rule = AutomationRule::where('organization_id', $request->user()->organization_id)
            ->with(['logs' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(50);
            }])
            ->findOrFail($id);

        return response()->json(['data' => $rule]);
    }

    /**
     * Update automation rule.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $rule = AutomationRule::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $rule->update($request->only([
            'name', 'description', 'status', 'scope', 'platform', 'conditions',
            'condition_logic', 'actions', 'time_window', 'check_interval_minutes',
            'cooldown_minutes', 'target_ids', 'apply_to_all',
        ]));

        return response()->json([
            'message' => 'Automation rule updated.',
            'data' => $rule->fresh(),
        ]);
    }

    /**
     * Delete automation rule.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $rule = AutomationRule::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $rule->delete();

        return response()->json(['message' => 'Automation rule deleted.']);
    }

    /**
     * Get automation logs.
     */
    public function logs(Request $request): JsonResponse
    {
        $logs = AutomationLog::where('organization_id', $request->user()->organization_id)
            ->with('rule')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($logs);
    }

    /**
     * Test run an automation rule (dry run).
     */
    public function testRun(Request $request, int $id): JsonResponse
    {
        $rule = AutomationRule::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        // TODO: Implement dry run that evaluates conditions without executing actions

        return response()->json([
            'message' => 'Test run completed.',
            'data' => [
                'rule_id' => $rule->id,
                'would_trigger' => true,
                'matching_targets' => [],
            ],
        ]);
    }
}
