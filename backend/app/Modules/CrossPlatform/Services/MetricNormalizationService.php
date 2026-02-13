<?php

namespace App\Modules\CrossPlatform\Services;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Metrics\Models\CampaignMetric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MetricNormalizationService
{
    /**
     * Platform-specific metric mappings to unified schema.
     */
    private array $metricMappings = [
        'meta' => [
            'impressions' => 'impressions',
            'reach' => 'reach',
            'clicks' => 'link_clicks',
            'ctr' => 'ctr',
            'cpc' => 'cost_per_link_click',
            'cpm' => 'cpm',
            'spend' => 'spend',
            'conversions' => 'actions.offsite_conversion',
            'conversion_value' => 'action_values.offsite_conversion',
            'frequency' => 'frequency',
            'video_views' => 'video_p25_watched_actions',
        ],
        'google' => [
            'impressions' => 'impressions',
            'clicks' => 'clicks',
            'ctr' => 'ctr',
            'cpc' => 'average_cpc',
            'cpm' => 'average_cpm',
            'spend' => 'cost_micros',
            'conversions' => 'conversions',
            'conversion_value' => 'conversions_value',
            'video_views' => 'video_views',
        ],
    ];

    /**
     * Normalize metrics from platform-specific format to unified format.
     */
    public function normalize(string $platform, array $rawMetrics): array
    {
        $normalized = [];
        $mappings = $this->metricMappings[$platform] ?? [];

        foreach ($mappings as $unified => $platformKey) {
            $value = $this->extractValue($rawMetrics, $platformKey);

            // Platform-specific transformations
            if ($platform === 'google' && $unified === 'spend') {
                $value = $value / 1_000_000; // Google returns cost in micros
            }
            if ($platform === 'google' && $unified === 'ctr') {
                $value = $value * 100; // Google returns CTR as decimal
            }

            $normalized[$unified] = $value;
        }

        // Calculate derived metrics
        $normalized['roas'] = $normalized['spend'] > 0
            ? round($normalized['conversion_value'] / $normalized['spend'], 4)
            : 0;

        $normalized['cpa'] = $normalized['conversions'] > 0
            ? round($normalized['spend'] / $normalized['conversions'], 2)
            : 0;

        return $normalized;
    }

    /**
     * Get unified metrics across platforms for an organization.
     */
    public function getUnifiedMetrics(int $orgId, string $dateFrom, string $dateTo): array
    {
        $metrics = CampaignMetric::whereHas('campaign', function ($q) use ($orgId) {
            $q->where('organization_id', $orgId);
        })
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->with('campaign:id,platform,name')
        ->get();

        // Group by platform
        $byPlatform = $metrics->groupBy(fn($m) => $m->campaign->platform);

        $unified = [];
        foreach ($byPlatform as $platform => $platformMetrics) {
            $unified[$platform] = [
                'spend' => round($platformMetrics->sum('spend'), 2),
                'impressions' => $platformMetrics->sum('impressions'),
                'clicks' => $platformMetrics->sum('clicks'),
                'conversions' => $platformMetrics->sum('conversions'),
                'revenue' => round($platformMetrics->sum('revenue'), 2),
                'roas' => $platformMetrics->sum('spend') > 0
                    ? round($platformMetrics->sum('revenue') / $platformMetrics->sum('spend'), 4)
                    : 0,
                'ctr' => $platformMetrics->sum('impressions') > 0
                    ? round(($platformMetrics->sum('clicks') / $platformMetrics->sum('impressions')) * 100, 4)
                    : 0,
                'cpa' => $platformMetrics->sum('conversions') > 0
                    ? round($platformMetrics->sum('spend') / $platformMetrics->sum('conversions'), 2)
                    : 0,
                'campaign_count' => $platformMetrics->pluck('campaign_id')->unique()->count(),
            ];
        }

        // Total across platforms
        $unified['total'] = [
            'spend' => round($metrics->sum('spend'), 2),
            'impressions' => $metrics->sum('impressions'),
            'clicks' => $metrics->sum('clicks'),
            'conversions' => $metrics->sum('conversions'),
            'revenue' => round($metrics->sum('revenue'), 2),
            'roas' => $metrics->sum('spend') > 0
                ? round($metrics->sum('revenue') / $metrics->sum('spend'), 4)
                : 0,
            'ctr' => $metrics->sum('impressions') > 0
                ? round(($metrics->sum('clicks') / $metrics->sum('impressions')) * 100, 4)
                : 0,
            'cpa' => $metrics->sum('conversions') > 0
                ? round($metrics->sum('spend') / $metrics->sum('conversions'), 2)
                : 0,
        ];

        return $unified;
    }

    /**
     * Get daily unified metrics for charting.
     */
    public function getDailyUnifiedMetrics(int $orgId, string $dateFrom, string $dateTo): array
    {
        return CampaignMetric::whereHas('campaign', function ($q) use ($orgId) {
            $q->where('organization_id', $orgId);
        })
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->select([
            'date',
            DB::raw('SUM(spend) as spend'),
            DB::raw('SUM(impressions) as impressions'),
            DB::raw('SUM(clicks) as clicks'),
            DB::raw('SUM(conversions) as conversions'),
            DB::raw('SUM(revenue) as revenue'),
            DB::raw('CASE WHEN SUM(spend) > 0 THEN ROUND(SUM(revenue) / SUM(spend), 4) ELSE 0 END as roas'),
            DB::raw('CASE WHEN SUM(impressions) > 0 THEN ROUND(SUM(clicks) / SUM(impressions) * 100, 4) ELSE 0 END as ctr'),
        ])
        ->groupBy('date')
        ->orderBy('date')
        ->get()
        ->toArray();
    }

    private function extractValue(array $data, string $key): float
    {
        // Support dot notation for nested keys
        $keys = explode('.', $key);
        $value = $data;
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return 0;
            }
        }
        return is_numeric($value) ? (float) $value : 0;
    }
}
