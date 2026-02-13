<?php

namespace App\Modules\Campaigns\Services;

use App\Modules\AdAccounts\Services\MetaAdsService;
use App\Modules\AdAccounts\Services\GoogleAdsService;
use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    public function __construct(
        private MetaAdsService $metaService,
        private GoogleAdsService $googleService
    ) {}

    /**
     * Update campaign status on the platform and locally.
     */
    public function updateStatus(Campaign $campaign, string $status): void
    {
        $adAccount = $campaign->adAccount;

        try {
            match ($campaign->platform) {
                'meta' => $this->metaService->updateCampaignStatus(
                    $adAccount,
                    $campaign->platform_campaign_id,
                    $status === 'active' ? 'ACTIVE' : 'PAUSED'
                ),
                'google' => $this->googleService->updateCampaignStatus(
                    $adAccount,
                    $campaign->platform_campaign_id,
                    $status
                ),
            };

            $campaign->update(['status' => $status]);

            // Log the action
            $this->logAction($campaign, 'status_update', ['new_status' => $status]);
        } catch (\Exception $e) {
            Log::error("Failed to update campaign {$campaign->id} status: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update campaign budget on the platform and locally.
     */
    public function updateBudget(Campaign $campaign, float $dailyBudget): void
    {
        $adAccount = $campaign->adAccount;
        $oldBudget = $campaign->daily_budget;

        try {
            match ($campaign->platform) {
                'meta' => $this->metaService->updateCampaignBudget(
                    $adAccount,
                    $campaign->platform_campaign_id,
                    $dailyBudget
                ),
                'google' => $this->googleService->updateCampaignBudget(
                    $adAccount,
                    $campaign->platform_campaign_id,
                    $dailyBudget
                ),
            };

            $campaign->update(['daily_budget' => $dailyBudget]);

            $this->logAction($campaign, 'budget_update', [
                'old_budget' => $oldBudget,
                'new_budget' => $dailyBudget,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update campaign {$campaign->id} budget: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Log campaign action to audit log.
     */
    private function logAction(Campaign $campaign, string $action, array $data): void
    {
        \DB::table('audit_logs')->insert([
            'organization_id' => $campaign->organization_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->id,
            'new_values' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
