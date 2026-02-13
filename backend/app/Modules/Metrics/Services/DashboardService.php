<?php

namespace App\Modules\Metrics\Services;

use App\Modules\Metrics\Models\CampaignMetric;
use App\Modules\Metrics\Models\DailySummary;
use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Get KPI summary with comparison to previous period.
     */
    public function getKPISummary(int $orgId, string $dateFrom, string $dateTo, string $platform = 'all'): array
    {
        $cacheKey = "dashboard:kpi:{$orgId}:{$dateFrom}:{$dateTo}:{$platform}";

        return Cache::remember($cacheKey, 300, function () use ($orgId, $dateFrom, $dateTo, $platform) {
            $current = $this->getAggregatedMetrics($orgId, $dateFrom, $dateTo, $platform);

            // Calculate previous period
            $daysDiff = now()->parse($dateFrom)->diffInDays(now()->parse($dateTo));
            $prevFrom = now()->parse($dateFrom)->subDays($daysDiff + 1)->format('Y-m-d');
            $prevTo = now()->parse($dateFrom)->subDay()->format('Y-m-d');
            $previous = $this->getAggregatedMetrics($orgId, $prevFrom, $prevTo, $platform);

            return [
                'current_period' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'kpis' => [
                    'spend' => [
                        'value' => $current['total_spend'],
                        'previous' => $previous['total_spend'],
                        'change_percent' => $this->calcChange($current['total_spend'], $previous['total_spend']),
                    ],
                    'revenue' => [
                        'value' => $current['total_revenue'],
                        'previous' => $previous['total_revenue'],
                        'change_percent' => $this->calcChange($current['total_revenue'], $previous['total_revenue']),
                    ],
                    'roas' => [
                        'value' => $current['total_spend'] > 0 ? round($current['total_revenue'] / $current['total_spend'], 2) : 0,
                        'previous' => $previous['total_spend'] > 0 ? round($previous['total_revenue'] / $previous['total_spend'], 2) : 0,
                        'change_percent' => $this->calcChange(
                            $current['total_spend'] > 0 ? $current['total_revenue'] / $current['total_spend'] : 0,
                            $previous['total_spend'] > 0 ? $previous['total_revenue'] / $previous['total_spend'] : 0
                        ),
                    ],
                    'conversions' => [
                        'value' => $current['total_conversions'],
                        'previous' => $previous['total_conversions'],
                        'change_percent' => $this->calcChange($current['total_conversions'], $previous['total_conversions']),
                    ],
                    'cpc' => [
                        'value' => $current['total_clicks'] > 0 ? round($current['total_spend'] / $current['total_clicks'], 2) : 0,
                        'previous' => $previous['total_clicks'] > 0 ? round($previous['total_spend'] / $previous['total_clicks'], 2) : 0,
                    ],
                    'ctr' => [
                        'value' => $current['total_impressions'] > 0 ? round(($current['total_clicks'] / $current['total_impressions']) * 100, 2) : 0,
                        'previous' => $previous['total_impressions'] > 0 ? round(($previous['total_clicks'] / $previous['total_impressions']) * 100, 2) : 0,
                    ],
                    'impressions' => [
                        'value' => $current['total_impressions'],
                        'previous' => $previous['total_impressions'],
                        'change_percent' => $this->calcChange($current['total_impressions'], $previous['total_impressions']),
                    ],
                    'clicks' => [
                        'value' => $current['total_clicks'],
                        'previous' => $previous['total_clicks'],
                        'change_percent' => $this->calcChange($current['total_clicks'], $previous['total_clicks']),
                    ],
                ],
                'active_campaigns' => Campaign::where('organization_id', $orgId)->where('status', 'active')->count(),
            ];
        });
    }

    /**
     * Get campaign performance table data.
     */
    public function getCampaignPerformance(int $orgId, string $dateFrom, string $dateTo, string $platform, string $sortBy, string $sortDir): array
    {
        $query = DB::table('campaign_metrics as cm')
            ->join('campaigns as c', 'cm.campaign_id', '=', 'c.id')
            ->where('cm.organization_id', $orgId)
            ->whereBetween('cm.date', [$dateFrom, $dateTo]);

        if ($platform !== 'all') {
            $query->where('cm.platform', $platform);
        }

        $campaigns = $query->select([
            'c.id', 'c.name', 'c.platform', 'c.status', 'c.daily_budget',
            DB::raw('SUM(cm.impressions) as impressions'),
            DB::raw('SUM(cm.clicks) as clicks'),
            DB::raw('SUM(cm.spend) as spend'),
            DB::raw('SUM(cm.conversions) as conversions'),
            DB::raw('SUM(cm.revenue) as revenue'),
            DB::raw('CASE WHEN SUM(cm.clicks) > 0 THEN SUM(cm.spend) / SUM(cm.clicks) ELSE 0 END as cpc'),
            DB::raw('CASE WHEN SUM(cm.impressions) > 0 THEN (SUM(cm.clicks) / SUM(cm.impressions)) * 100 ELSE 0 END as ctr'),
            DB::raw('CASE WHEN SUM(cm.spend) > 0 THEN SUM(cm.revenue) / SUM(cm.spend) ELSE 0 END as roas'),
            DB::raw('CASE WHEN SUM(cm.conversions) > 0 THEN SUM(cm.spend) / SUM(cm.conversions) ELSE 0 END as cpa'),
        ])
        ->groupBy('c.id', 'c.name', 'c.platform', 'c.status', 'c.daily_budget')
        ->orderBy($sortBy, $sortDir)
        ->get();

        return $campaigns->toArray();
    }

    /**
     * Get time-based comparison.
     */
    public function getTimeComparison(int $orgId, string $dateFrom, string $dateTo, string $compareFrom, string $compareTo): array
    {
        $current = $this->getAggregatedMetrics($orgId, $dateFrom, $dateTo);
        $comparison = $this->getAggregatedMetrics($orgId, $compareFrom, $compareTo);

        $metrics = ['total_spend', 'total_revenue', 'total_impressions', 'total_clicks', 'total_conversions'];
        $result = [];

        foreach ($metrics as $metric) {
            $result[$metric] = [
                'current' => $current[$metric],
                'comparison' => $comparison[$metric],
                'change_percent' => $this->calcChange($current[$metric], $comparison[$metric]),
            ];
        }

        // Daily breakdown for chart
        $currentDaily = DailySummary::where('organization_id', $orgId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->select('date', DB::raw('SUM(total_spend) as spend'), DB::raw('SUM(total_revenue) as revenue'),
                DB::raw('SUM(total_conversions) as conversions'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $result['daily_breakdown'] = $currentDaily;

        return $result;
    }

    /**
     * Get aggregated metrics for a period.
     */
    private function getAggregatedMetrics(int $orgId, string $dateFrom, string $dateTo, string $platform = 'all'): array
    {
        $query = CampaignMetric::where('organization_id', $orgId)
            ->whereBetween('date', [$dateFrom, $dateTo]);

        if ($platform !== 'all') {
            $query->where('platform', $platform);
        }

        return [
            'total_spend' => round($query->sum('spend'), 2),
            'total_revenue' => round((clone $query)->sum('revenue'), 2),
            'total_impressions' => (clone $query)->sum('impressions'),
            'total_clicks' => (clone $query)->sum('clicks'),
            'total_conversions' => (clone $query)->sum('conversions'),
        ];
    }

    private function calcChange(float $current, float $previous): float
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
