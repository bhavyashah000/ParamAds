<?php

namespace App\Modules\Metrics\Services;

use App\Modules\AdAccounts\Models\AdAccount;
use App\Modules\AdAccounts\Services\MetaAdsService;
use App\Modules\AdAccounts\Services\GoogleAdsService;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Metrics\Models\CampaignMetric;
use App\Modules\Metrics\Models\DailySummary;
use Illuminate\Support\Facades\Log;

class MetricsSyncService
{
    public function __construct(
        private MetaAdsService $metaService,
        private GoogleAdsService $googleService
    ) {}

    /**
     * Sync metrics for an ad account.
     */
    public function syncAdAccount(AdAccount $adAccount, string $dateStart = null, string $dateEnd = null): void
    {
        $dateStart = $dateStart ?? now()->subDays(1)->format('Y-m-d');
        $dateEnd = $dateEnd ?? now()->format('Y-m-d');

        match ($adAccount->platform) {
            'meta' => $this->syncMetaMetrics($adAccount, $dateStart, $dateEnd),
            'google' => $this->syncGoogleMetrics($adAccount, $dateStart, $dateEnd),
        };

        // Rebuild daily summaries
        $this->rebuildDailySummaries($adAccount, $dateStart, $dateEnd);
    }

    /**
     * Sync Meta campaign metrics.
     */
    private function syncMetaMetrics(AdAccount $adAccount, string $dateStart, string $dateEnd): void
    {
        $campaigns = Campaign::where('ad_account_id', $adAccount->id)->get();

        foreach ($campaigns as $campaign) {
            try {
                $insights = $this->metaService->getCampaignInsights(
                    $adAccount,
                    $campaign->platform_campaign_id,
                    $dateStart,
                    $dateEnd
                );

                foreach ($insights as $dayData) {
                    $conversions = $this->extractMetaConversions($dayData);
                    $revenue = $this->extractMetaRevenue($dayData);

                    $spend = floatval($dayData['spend'] ?? 0);
                    $clicks = intval($dayData['clicks'] ?? 0);
                    $impressions = intval($dayData['impressions'] ?? 0);

                    CampaignMetric::updateOrCreate(
                        [
                            'campaign_id' => $campaign->id,
                            'date' => $dayData['date_start'],
                            'platform' => 'meta',
                        ],
                        [
                            'organization_id' => $adAccount->organization_id,
                            'ad_account_id' => $adAccount->id,
                            'impressions' => $impressions,
                            'clicks' => $clicks,
                            'spend' => $spend,
                            'conversions' => $conversions,
                            'revenue' => $revenue,
                            'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                            'cpm' => $impressions > 0 ? ($spend / $impressions) * 1000 : 0,
                            'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
                            'roas' => $spend > 0 ? $revenue / $spend : 0,
                            'cpa' => $conversions > 0 ? $spend / $conversions : 0,
                            'reach' => intval($dayData['reach'] ?? 0),
                            'frequency' => floatval($dayData['frequency'] ?? 0),
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error("Meta metrics sync failed for campaign {$campaign->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Sync Google Ads campaign metrics.
     */
    private function syncGoogleMetrics(AdAccount $adAccount, string $dateStart, string $dateEnd): void
    {
        try {
            $results = $this->googleService->getCampaignMetrics($adAccount, $dateStart, $dateEnd);

            foreach ($results as $row) {
                $campaignData = $row['campaign'] ?? [];
                $metrics = $row['metrics'] ?? [];
                $date = $row['segments']['date'] ?? null;

                if (!$date) continue;

                $campaign = Campaign::where('ad_account_id', $adAccount->id)
                    ->where('platform_campaign_id', $campaignData['id'] ?? '')
                    ->first();

                if (!$campaign) continue;

                $spend = ($metrics['costMicros'] ?? 0) / 1000000;
                $clicks = intval($metrics['clicks'] ?? 0);
                $impressions = intval($metrics['impressions'] ?? 0);
                $conversions = intval($metrics['conversions'] ?? 0);
                $revenue = floatval($metrics['conversionsValue'] ?? 0);

                CampaignMetric::updateOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'date' => $date,
                        'platform' => 'google',
                    ],
                    [
                        'organization_id' => $adAccount->organization_id,
                        'ad_account_id' => $adAccount->id,
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                        'spend' => $spend,
                        'conversions' => $conversions,
                        'revenue' => $revenue,
                        'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                        'cpm' => $impressions > 0 ? ($spend / $impressions) * 1000 : 0,
                        'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
                        'roas' => $spend > 0 ? $revenue / $spend : 0,
                        'cpa' => $conversions > 0 ? $spend / $conversions : 0,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("Google metrics sync failed for ad account {$adAccount->id}: " . $e->getMessage());
        }
    }

    /**
     * Rebuild daily summary aggregations.
     */
    public function rebuildDailySummaries(AdAccount $adAccount, string $dateStart, string $dateEnd): void
    {
        $metrics = CampaignMetric::where('ad_account_id', $adAccount->id)
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->get()
            ->groupBy('date');

        foreach ($metrics as $date => $dayMetrics) {
            $totalSpend = $dayMetrics->sum('spend');
            $totalClicks = $dayMetrics->sum('clicks');
            $totalImpressions = $dayMetrics->sum('impressions');
            $totalConversions = $dayMetrics->sum('conversions');
            $totalRevenue = $dayMetrics->sum('revenue');

            DailySummary::updateOrCreate(
                [
                    'organization_id' => $adAccount->organization_id,
                    'ad_account_id' => $adAccount->id,
                    'date' => $date,
                    'platform' => $adAccount->platform,
                ],
                [
                    'total_campaigns' => $dayMetrics->pluck('campaign_id')->unique()->count(),
                    'active_campaigns' => $dayMetrics->where('spend', '>', 0)->pluck('campaign_id')->unique()->count(),
                    'total_spend' => $totalSpend,
                    'total_revenue' => $totalRevenue,
                    'total_impressions' => $totalImpressions,
                    'total_clicks' => $totalClicks,
                    'total_conversions' => $totalConversions,
                    'avg_cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0,
                    'avg_cpm' => $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0,
                    'avg_ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
                    'avg_roas' => $totalSpend > 0 ? $totalRevenue / $totalSpend : 0,
                    'avg_cpa' => $totalConversions > 0 ? $totalSpend / $totalConversions : 0,
                ]
            );
        }
    }

    /**
     * Extract conversions from Meta actions array.
     */
    private function extractMetaConversions(array $data): int
    {
        $actions = $data['actions'] ?? [];
        foreach ($actions as $action) {
            if (in_array($action['action_type'], ['offsite_conversion.fb_pixel_purchase', 'purchase'])) {
                return intval($action['value']);
            }
        }
        return 0;
    }

    /**
     * Extract revenue from Meta action_values array.
     */
    private function extractMetaRevenue(array $data): float
    {
        $actionValues = $data['action_values'] ?? [];
        foreach ($actionValues as $av) {
            if (in_array($av['action_type'], ['offsite_conversion.fb_pixel_purchase', 'purchase'])) {
                return floatval($av['value']);
            }
        }
        return 0;
    }
}
