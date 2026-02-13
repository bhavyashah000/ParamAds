<?php

namespace App\Modules\Campaigns\Jobs;

use App\Modules\AdAccounts\Models\AdAccount;
use App\Modules\AdAccounts\Services\MetaAdsService;
use App\Modules\AdAccounts\Services\GoogleAdsService;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\AdSet;
use App\Modules\Campaigns\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private AdAccount $adAccount
    ) {}

    public function handle(): void
    {
        try {
            match ($this->adAccount->platform) {
                'meta' => $this->syncMeta(),
                'google' => $this->syncGoogle(),
            };

            $this->adAccount->update(['last_synced_at' => now()]);
        } catch (\Exception $e) {
            Log::error("Campaign sync failed for ad account {$this->adAccount->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function syncMeta(): void
    {
        $metaService = app(MetaAdsService::class);
        $campaigns = $metaService->getCampaigns($this->adAccount);

        foreach ($campaigns as $campaignData) {
            $campaign = Campaign::updateOrCreate(
                [
                    'ad_account_id' => $this->adAccount->id,
                    'platform_campaign_id' => $campaignData['id'],
                ],
                [
                    'organization_id' => $this->adAccount->organization_id,
                    'platform' => 'meta',
                    'name' => $campaignData['name'],
                    'status' => strtolower($campaignData['status']),
                    'objective' => $campaignData['objective'] ?? null,
                    'daily_budget' => isset($campaignData['daily_budget'])
                        ? $campaignData['daily_budget'] / 100
                        : null,
                    'lifetime_budget' => isset($campaignData['lifetime_budget'])
                        ? $campaignData['lifetime_budget'] / 100
                        : null,
                    'platform_data' => $campaignData,
                    'last_synced_at' => now(),
                ]
            );

            // Sync ad sets
            $adSets = $metaService->getAdSets($this->adAccount, $campaignData['id']);
            foreach ($adSets as $adSetData) {
                $adSet = AdSet::updateOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'platform_adset_id' => $adSetData['id'],
                    ],
                    [
                        'organization_id' => $this->adAccount->organization_id,
                        'platform' => 'meta',
                        'name' => $adSetData['name'],
                        'status' => strtolower($adSetData['status']),
                        'daily_budget' => isset($adSetData['daily_budget'])
                            ? $adSetData['daily_budget'] / 100
                            : null,
                        'targeting' => $adSetData['targeting'] ?? null,
                        'platform_data' => $adSetData,
                        'last_synced_at' => now(),
                    ]
                );

                // Sync ads
                $ads = $metaService->getAds($this->adAccount, $adSetData['id']);
                foreach ($ads as $adData) {
                    Ad::updateOrCreate(
                        [
                            'ad_set_id' => $adSet->id,
                            'platform_ad_id' => $adData['id'],
                        ],
                        [
                            'organization_id' => $this->adAccount->organization_id,
                            'campaign_id' => $campaign->id,
                            'platform' => 'meta',
                            'name' => $adData['name'],
                            'status' => strtolower($adData['status']),
                            'creative_type' => $adData['creative']['type'] ?? null,
                            'headline' => $adData['creative']['title'] ?? null,
                            'body' => $adData['creative']['body'] ?? null,
                            'cta' => $adData['creative']['call_to_action_type'] ?? null,
                            'image_url' => $adData['creative']['image_url'] ?? null,
                            'platform_data' => $adData,
                            'last_synced_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    private function syncGoogle(): void
    {
        $googleService = app(GoogleAdsService::class);
        $campaigns = $googleService->getCampaigns($this->adAccount);

        foreach ($campaigns as $row) {
            $campaignData = $row['campaign'] ?? [];
            $budgetData = $row['campaignBudget'] ?? [];

            Campaign::updateOrCreate(
                [
                    'ad_account_id' => $this->adAccount->id,
                    'platform_campaign_id' => $campaignData['id'] ?? '',
                ],
                [
                    'organization_id' => $this->adAccount->organization_id,
                    'platform' => 'google',
                    'name' => $campaignData['name'] ?? 'Unknown',
                    'status' => strtolower($campaignData['status'] ?? 'unknown'),
                    'objective' => $campaignData['advertisingChannelType'] ?? null,
                    'daily_budget' => isset($budgetData['amountMicros'])
                        ? $budgetData['amountMicros'] / 1000000
                        : null,
                    'start_date' => $campaignData['startDate'] ?? null,
                    'end_date' => $campaignData['endDate'] ?? null,
                    'platform_data' => $row,
                    'last_synced_at' => now(),
                ]
            );
        }
    }
}
