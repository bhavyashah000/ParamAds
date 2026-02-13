<?php

namespace App\Modules\Automation\Services;

use App\Modules\Automation\Models\AutomationRule;
use App\Modules\Automation\Models\AutomationLog;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\AdSet;
use App\Modules\Campaigns\Models\Ad;
use App\Modules\Campaigns\Services\CampaignService;
use App\Modules\Metrics\Models\CampaignMetric;
use Illuminate\Support\Facades\Log;

class AutomationEngine
{
    public function __construct(
        private CampaignService $campaignService
    ) {}

    /**
     * Process all active automation rules.
     */
    public function processAllRules(): void
    {
        AutomationRule::active()
            ->where(function ($q) {
                $q->whereNull('last_executed_at')
                    ->orWhereRaw('last_executed_at <= DATE_SUB(NOW(), INTERVAL check_interval_minutes MINUTE)');
            })
            ->chunk(50, function ($rules) {
                foreach ($rules as $rule) {
                    $this->processRule($rule);
                }
            });
    }

    /**
     * Process a single automation rule.
     */
    public function processRule(AutomationRule $rule): void
    {
        if ($rule->isOnCooldown()) {
            return;
        }

        try {
            $targets = $this->getTargets($rule);

            foreach ($targets as $target) {
                $conditionsMet = $this->evaluateConditions($rule, $target);

                if ($conditionsMet) {
                    $this->executeActions($rule, $target);
                }
            }
        } catch (\Exception $e) {
            Log::error("Automation rule {$rule->id} processing failed: " . $e->getMessage());
        }
    }

    /**
     * Get target entities for the rule.
     */
    private function getTargets(AutomationRule $rule): \Illuminate\Support\Collection
    {
        $query = match ($rule->scope) {
            'campaign' => Campaign::where('organization_id', $rule->organization_id),
            'adset' => AdSet::where('organization_id', $rule->organization_id),
            'ad' => Ad::where('organization_id', $rule->organization_id),
            default => Campaign::where('organization_id', $rule->organization_id),
        };

        if ($rule->platform && $rule->platform !== 'all') {
            $query->where('platform', $rule->platform);
        }

        if (!$rule->apply_to_all && !empty($rule->target_ids)) {
            $query->whereIn('id', $rule->target_ids);
        }

        return $query->where('status', 'active')->get();
    }

    /**
     * Evaluate all conditions for a target using AND/OR logic.
     */
    private function evaluateConditions(AutomationRule $rule, $target): bool
    {
        $conditions = $rule->conditions;
        $logic = $rule->condition_logic;

        if (empty($conditions)) {
            return false;
        }

        $results = [];
        foreach ($conditions as $condition) {
            $results[] = $this->evaluateSingleCondition($condition, $target, $rule->time_window);
        }

        return match ($logic) {
            'AND' => !in_array(false, $results),
            'OR' => in_array(true, $results),
            default => !in_array(false, $results),
        };
    }

    /**
     * Evaluate a single condition against target metrics.
     *
     * Condition format:
     * {
     *   "metric": "spend|cpc|ctr|roas|cpa|impressions|clicks|conversions|frequency",
     *   "operator": ">|<|>=|<=|==|!=|increase_by|decrease_by",
     *   "value": 100,
     *   "time_window": "24h|3d|7d" (optional override),
     *   "compare_to": "previous_period" (optional, for delta comparison)
     * }
     */
    private function evaluateSingleCondition(array $condition, $target, string $defaultTimeWindow): bool
    {
        $metric = $condition['metric'];
        $operator = $condition['operator'];
        $threshold = floatval($condition['value']);
        $timeWindow = $condition['time_window'] ?? $defaultTimeWindow;
        $compareTo = $condition['compare_to'] ?? null;

        // Get metric value for the time window
        $currentValue = $this->getMetricValue($target, $metric, $timeWindow);

        // Handle delta comparison
        if ($compareTo === 'previous_period' || in_array($operator, ['increase_by', 'decrease_by'])) {
            $previousValue = $this->getPreviousPeriodMetricValue($target, $metric, $timeWindow);

            if ($previousValue == 0) return false;

            $deltaPercent = (($currentValue - $previousValue) / $previousValue) * 100;

            return match ($operator) {
                'increase_by' => $deltaPercent >= $threshold,
                'decrease_by' => $deltaPercent <= -$threshold,
                default => $this->compareValues($deltaPercent, $operator, $threshold),
            };
        }

        return $this->compareValues($currentValue, $operator, $threshold);
    }

    /**
     * Get aggregated metric value for a time window.
     */
    private function getMetricValue($target, string $metric, string $timeWindow): float
    {
        $days = $this->timeWindowToDays($timeWindow);
        $startDate = now()->subDays($days)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $campaignId = $target instanceof Campaign ? $target->id : $target->campaign_id;

        $query = CampaignMetric::where('campaign_id', $campaignId)
            ->whereBetween('date', [$startDate, $endDate]);

        return match ($metric) {
            'spend' => $query->sum('spend'),
            'impressions' => $query->sum('impressions'),
            'clicks' => $query->sum('clicks'),
            'conversions' => $query->sum('conversions'),
            'revenue' => $query->sum('revenue'),
            'cpc' => $this->safeAvg($query, 'cpc'),
            'cpm' => $this->safeAvg($query, 'cpm'),
            'ctr' => $this->safeAvg($query, 'ctr'),
            'roas' => $this->calculateRoas($query),
            'cpa' => $this->calculateCpa($query),
            'frequency' => $this->safeAvg($query, 'frequency'),
            default => 0,
        };
    }

    /**
     * Get metric value for the previous equivalent period.
     */
    private function getPreviousPeriodMetricValue($target, string $metric, string $timeWindow): float
    {
        $days = $this->timeWindowToDays($timeWindow);
        $startDate = now()->subDays($days * 2)->format('Y-m-d');
        $endDate = now()->subDays($days)->format('Y-m-d');

        $campaignId = $target instanceof Campaign ? $target->id : $target->campaign_id;

        $query = CampaignMetric::where('campaign_id', $campaignId)
            ->whereBetween('date', [$startDate, $endDate]);

        return match ($metric) {
            'spend' => $query->sum('spend'),
            'impressions' => $query->sum('impressions'),
            'clicks' => $query->sum('clicks'),
            'conversions' => $query->sum('conversions'),
            'revenue' => $query->sum('revenue'),
            'cpc' => $this->safeAvg($query, 'cpc'),
            'ctr' => $this->safeAvg($query, 'ctr'),
            'roas' => $this->calculateRoas($query),
            'cpa' => $this->calculateCpa($query),
            default => 0,
        };
    }

    /**
     * Execute automation actions on a target.
     */
    private function executeActions(AutomationRule $rule, $target): void
    {
        foreach ($rule->actions as $action) {
            try {
                $result = $this->executeSingleAction($action, $target);

                AutomationLog::create([
                    'organization_id' => $rule->organization_id,
                    'automation_rule_id' => $rule->id,
                    'target_type' => $rule->scope,
                    'target_id' => $target->id,
                    'action_type' => $action['type'],
                    'condition_snapshot' => $rule->conditions,
                    'action_result' => $result,
                    'status' => 'success',
                ]);
            } catch (\Exception $e) {
                AutomationLog::create([
                    'organization_id' => $rule->organization_id,
                    'automation_rule_id' => $rule->id,
                    'target_type' => $rule->scope,
                    'target_id' => $target->id,
                    'action_type' => $action['type'],
                    'condition_snapshot' => $rule->conditions,
                    'action_result' => ['error' => $e->getMessage()],
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        $rule->update([
            'last_executed_at' => now(),
            'execution_count' => $rule->execution_count + 1,
        ]);
    }

    /**
     * Execute a single action.
     *
     * Action format:
     * {
     *   "type": "pause|activate|increase_budget|decrease_budget|alert|email",
     *   "params": {"amount": 20, "unit": "percent|absolute"}
     * }
     */
    private function executeSingleAction(array $action, $target): array
    {
        $type = $action['type'];
        $params = $action['params'] ?? [];

        return match ($type) {
            'pause' => $this->actionPause($target),
            'activate' => $this->actionActivate($target),
            'increase_budget' => $this->actionAdjustBudget($target, $params, 'increase'),
            'decrease_budget' => $this->actionAdjustBudget($target, $params, 'decrease'),
            'alert' => $this->actionAlert($target, $params),
            default => ['message' => "Unknown action type: {$type}"],
        };
    }

    private function actionPause($target): array
    {
        if ($target instanceof Campaign) {
            $this->campaignService->updateStatus($target, 'paused');
        }
        return ['action' => 'paused', 'target_id' => $target->id];
    }

    private function actionActivate($target): array
    {
        if ($target instanceof Campaign) {
            $this->campaignService->updateStatus($target, 'active');
        }
        return ['action' => 'activated', 'target_id' => $target->id];
    }

    private function actionAdjustBudget($target, array $params, string $direction): array
    {
        if (!($target instanceof Campaign)) {
            return ['error' => 'Budget adjustment only supported for campaigns'];
        }

        $amount = floatval($params['amount'] ?? 10);
        $unit = $params['unit'] ?? 'percent';
        $currentBudget = $target->daily_budget;

        if ($unit === 'percent') {
            $change = $currentBudget * ($amount / 100);
        } else {
            $change = $amount;
        }

        $newBudget = $direction === 'increase'
            ? $currentBudget + $change
            : max(1, $currentBudget - $change);

        $this->campaignService->updateBudget($target, $newBudget);

        return [
            'action' => "budget_{$direction}",
            'old_budget' => $currentBudget,
            'new_budget' => $newBudget,
            'change' => $change,
        ];
    }

    private function actionAlert($target, array $params): array
    {
        \DB::table('alerts')->insert([
            'organization_id' => $target->organization_id,
            'type' => 'automation',
            'severity' => $params['severity'] ?? 'warning',
            'title' => $params['title'] ?? "Automation triggered for {$target->name}",
            'message' => $params['message'] ?? "Automation rule conditions met.",
            'data' => json_encode(['target_id' => $target->id, 'target_type' => class_basename($target)]),
            'channel' => $params['channel'] ?? 'in_app',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['action' => 'alert_created', 'target_id' => $target->id];
    }

    // Helpers
    private function compareValues(float $actual, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '>' => $actual > $threshold,
            '<' => $actual < $threshold,
            '>=' => $actual >= $threshold,
            '<=' => $actual <= $threshold,
            '==' => abs($actual - $threshold) < 0.001,
            '!=' => abs($actual - $threshold) >= 0.001,
            default => false,
        };
    }

    private function timeWindowToDays(string $window): int
    {
        return match ($window) {
            '1h' => 0, // same day
            '6h' => 0,
            '12h' => 0,
            '24h', '1d' => 1,
            '3d' => 3,
            '7d' => 7,
            '14d' => 14,
            '30d' => 30,
            default => 1,
        };
    }

    private function safeAvg($query, string $column): float
    {
        return floatval($query->avg($column) ?? 0);
    }

    private function calculateRoas($query): float
    {
        $spend = $query->sum('spend');
        $revenue = $query->sum('revenue');
        return $spend > 0 ? $revenue / $spend : 0;
    }

    private function calculateCpa($query): float
    {
        $spend = $query->sum('spend');
        $conversions = $query->sum('conversions');
        return $conversions > 0 ? $spend / $conversions : 0;
    }
}
