<?php

namespace App\Modules\AdAccounts\Services;

use App\Modules\AdAccounts\Models\AdAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAdsService
{
    private string $oauthUrl = 'https://accounts.google.com/o/oauth2';
    private string $tokenUrl = 'https://oauth2.googleapis.com/token';
    private string $apiUrl = 'https://googleads.googleapis.com/v15';

    /**
     * Generate OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => config('services.google_ads.client_id'),
            'redirect_uri' => config('services.google_ads.redirect_uri'),
            'scope' => 'https://www.googleapis.com/auth/adwords',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return "{$this->oauthUrl}/auth?{$params}";
    }

    /**
     * Exchange authorization code for tokens.
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::post($this->tokenUrl, [
            'code' => $code,
            'client_id' => config('services.google_ads.client_id'),
            'client_secret' => config('services.google_ads.client_secret'),
            'redirect_uri' => config('services.google_ads.redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to exchange code for token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refresh access token using refresh token.
     */
    public function refreshToken(AdAccount $adAccount): void
    {
        try {
            $response = Http::post($this->tokenUrl, [
                'client_id' => config('services.google_ads.client_id'),
                'client_secret' => config('services.google_ads.client_secret'),
                'refresh_token' => $adAccount->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Token refresh failed: ' . $response->body());
            }

            $data = $response->json();

            $adAccount->update([
                'access_token' => $data['access_token'],
                'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            ]);
        } catch (\Exception $e) {
            Log::error("Google token refresh failed for ad account {$adAccount->id}: " . $e->getMessage());
            $adAccount->update(['status' => 'error']);
            throw $e;
        }
    }

    /**
     * Get accessible customer accounts.
     */
    public function getAccessibleCustomers(string $accessToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'developer-token' => config('services.google_ads.developer_token'),
        ])->get("{$this->apiUrl}/customers:listAccessibleCustomers");

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch customers: ' . $response->body());
        }

        return $response->json()['resourceNames'] ?? [];
    }

    /**
     * Execute a Google Ads query (GAQL).
     */
    public function query(AdAccount $adAccount, string $query): array
    {
        $this->ensureValidToken($adAccount);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$adAccount->access_token}",
            'developer-token' => config('services.google_ads.developer_token'),
        ])->post(
            "{$this->apiUrl}/customers/{$adAccount->platform_account_id}/googleAds:searchStream",
            ['query' => $query]
        );

        if (!$response->successful()) {
            throw new \Exception('Google Ads query failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get campaigns.
     */
    public function getCampaigns(AdAccount $adAccount): array
    {
        $gaql = "SELECT campaign.id, campaign.name, campaign.status, "
            . "campaign.advertising_channel_type, campaign_budget.amount_micros, "
            . "campaign.start_date, campaign.end_date "
            . "FROM campaign "
            . "WHERE campaign.status != 'REMOVED' "
            . "ORDER BY campaign.id";

        $result = $this->query($adAccount, $gaql);
        return $this->parseStreamResponse($result);
    }

    /**
     * Get ad groups for a campaign.
     */
    public function getAdGroups(AdAccount $adAccount, string $campaignId): array
    {
        $gaql = "SELECT ad_group.id, ad_group.name, ad_group.status, "
            . "ad_group.type, ad_group.cpc_bid_micros "
            . "FROM ad_group "
            . "WHERE campaign.id = {$campaignId} "
            . "AND ad_group.status != 'REMOVED'";

        $result = $this->query($adAccount, $gaql);
        return $this->parseStreamResponse($result);
    }

    /**
     * Get campaign metrics.
     */
    public function getCampaignMetrics(AdAccount $adAccount, string $dateStart, string $dateEnd): array
    {
        $gaql = "SELECT campaign.id, campaign.name, segments.date, "
            . "metrics.impressions, metrics.clicks, metrics.cost_micros, "
            . "metrics.conversions, metrics.conversions_value, "
            . "metrics.average_cpc, metrics.average_cpm, metrics.ctr "
            . "FROM campaign "
            . "WHERE segments.date BETWEEN '{$dateStart}' AND '{$dateEnd}' "
            . "AND campaign.status != 'REMOVED'";

        $result = $this->query($adAccount, $gaql);
        return $this->parseStreamResponse($result);
    }

    /**
     * Update campaign status.
     */
    public function updateCampaignStatus(AdAccount $adAccount, string $campaignId, string $status): array
    {
        $this->ensureValidToken($adAccount);

        $googleStatus = strtoupper($status) === 'ACTIVE' ? 'ENABLED' : 'PAUSED';

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$adAccount->access_token}",
            'developer-token' => config('services.google_ads.developer_token'),
        ])->post(
            "{$this->apiUrl}/customers/{$adAccount->platform_account_id}/campaigns:mutate",
            [
                'operations' => [[
                    'update' => [
                        'resourceName' => "customers/{$adAccount->platform_account_id}/campaigns/{$campaignId}",
                        'status' => $googleStatus,
                    ],
                    'updateMask' => 'status',
                ]],
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Failed to update campaign: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Update campaign budget.
     */
    public function updateCampaignBudget(AdAccount $adAccount, string $campaignId, float $dailyBudget): array
    {
        $this->ensureValidToken($adAccount);

        // Google Ads uses micros (1 dollar = 1,000,000 micros)
        $budgetMicros = (int) ($dailyBudget * 1000000);

        // First get the budget resource name
        $gaql = "SELECT campaign.id, campaign_budget.resource_name "
            . "FROM campaign WHERE campaign.id = {$campaignId}";
        $result = $this->query($adAccount, $gaql);
        $rows = $this->parseStreamResponse($result);

        if (empty($rows)) {
            throw new \Exception("Campaign {$campaignId} not found.");
        }

        $budgetResourceName = $rows[0]['campaignBudget']['resourceName'] ?? null;

        if (!$budgetResourceName) {
            throw new \Exception("Budget resource not found for campaign {$campaignId}.");
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$adAccount->access_token}",
            'developer-token' => config('services.google_ads.developer_token'),
        ])->post(
            "{$this->apiUrl}/customers/{$adAccount->platform_account_id}/campaignBudgets:mutate",
            [
                'operations' => [[
                    'update' => [
                        'resourceName' => $budgetResourceName,
                        'amountMicros' => $budgetMicros,
                    ],
                    'updateMask' => 'amountMicros',
                ]],
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Failed to update budget: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Ensure valid token.
     */
    private function ensureValidToken(AdAccount $adAccount): void
    {
        if ($adAccount->isTokenExpired()) {
            $this->refreshToken($adAccount);
        }
    }

    /**
     * Parse Google Ads streaming response.
     */
    private function parseStreamResponse(array $response): array
    {
        $rows = [];
        foreach ($response as $batch) {
            if (isset($batch['results'])) {
                $rows = array_merge($rows, $batch['results']);
            }
        }
        return $rows;
    }
}
