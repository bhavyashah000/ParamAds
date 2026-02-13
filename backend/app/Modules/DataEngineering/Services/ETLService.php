<?php

namespace App\Modules\DataEngineering\Services;

use App\Modules\Metrics\Models\CampaignMetric;
use App\Modules\Metrics\Models\DailySummary;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ETLService
{
    /**
     * Run daily aggregation ETL.
     * Aggregates campaign metrics into daily summaries per organization.
     */
    public function runDailyAggregation(string $date = null): void
    {
        $date = $date ?? now()->subDay()->format('Y-m-d');

        Log::info("ETL: Starting daily aggregation for {$date}");

        Organization::chunk(50, function ($organizations) use ($date) {
            foreach ($organizations as $org) {
                $this->aggregateForOrganization($org->id, $date);
            }
        });

        Log::info("ETL: Daily aggregation completed for {$date}");
    }

    /**
     * Aggregate metrics for a single organization.
     */
    public function aggregateForOrganization(int $orgId, string $date): void
    {
        try {
            $metrics = CampaignMetric::whereHas('campaign', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            })
            ->where('date', $date)
            ->get();

            if ($metrics->isEmpty()) return;

            // Aggregate by platform
            $byPlatform = $metrics->groupBy(fn($m) => $m->campaign->platform ?? 'unknown');

            foreach ($byPlatform as $platform => $platformMetrics) {
                DailySummary::updateOrCreate(
                    [
                        'organization_id' => $orgId,
                        'date' => $date,
                        'platform' => $platform,
                    ],
                    [
                        'total_spend' => round($platformMetrics->sum('spend'), 2),
                        'total_impressions' => $platformMetrics->sum('impressions'),
                        'total_clicks' => $platformMetrics->sum('clicks'),
                        'total_conversions' => $platformMetrics->sum('conversions'),
                        'total_revenue' => round($platformMetrics->sum('revenue'), 2),
                        'avg_ctr' => $platformMetrics->sum('impressions') > 0
                            ? round(($platformMetrics->sum('clicks') / $platformMetrics->sum('impressions')) * 100, 4) : 0,
                        'avg_cpc' => $platformMetrics->sum('clicks') > 0
                            ? round($platformMetrics->sum('spend') / $platformMetrics->sum('clicks'), 4) : 0,
                        'avg_cpa' => $platformMetrics->sum('conversions') > 0
                            ? round($platformMetrics->sum('spend') / $platformMetrics->sum('conversions'), 2) : 0,
                        'roas' => $platformMetrics->sum('spend') > 0
                            ? round($platformMetrics->sum('revenue') / $platformMetrics->sum('spend'), 4) : 0,
                        'campaign_count' => $platformMetrics->pluck('campaign_id')->unique()->count(),
                    ]
                );
            }

            // Invalidate dashboard cache
            Cache::tags(["org_{$orgId}_dashboard"])->flush();
        } catch (\Exception $e) {
            Log::error("ETL aggregation failed for org {$orgId}: " . $e->getMessage());
        }
    }

    /**
     * Run data cleanup - remove old raw metrics beyond retention period.
     */
    public function runDataCleanup(int $retentionDays = 90): void
    {
        $cutoffDate = now()->subDays($retentionDays)->format('Y-m-d');

        Log::info("ETL: Cleaning up data older than {$cutoffDate}");

        // Archive to cold storage before deletion (in production)
        $deleted = DB::table('ad_metrics')
            ->where('date', '<', $cutoffDate)
            ->delete();

        Log::info("ETL: Cleaned up {$deleted} ad_metrics records");

        // Clean old webhook logs
        $webhookDeleted = DB::table('webhook_logs')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        Log::info("ETL: Cleaned up {$webhookDeleted} webhook_logs records");

        // Clean old activity logs
        $activityDeleted = DB::table('activity_logs')
            ->where('created_at', '<', now()->subDays(180))
            ->delete();

        Log::info("ETL: Cleaned up {$activityDeleted} activity_logs records");
    }

    /**
     * Backfill missing daily summaries.
     */
    public function backfillSummaries(int $days = 30): void
    {
        for ($i = $days; $i >= 1; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $this->runDailyAggregation($date);
        }
    }
}
