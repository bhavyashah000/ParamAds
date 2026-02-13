<?php

namespace App\Modules\AdAccounts\Services;

use App\Modules\AdAccounts\Models\AdAccount;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsService
{
    private string $apiVersion;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiVersion = config('services.meta.api_version', 'v18.0');
        $this->baseUrl = "https://graph.facebook.com/{$this->apiVersion}";
    }

    /**
     * Generate OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => config('services.meta.app_id'),
            'redirect_uri' => config('services.meta.redirect_uri'),
            'scope' => 'ads_management,ads_read,business_management,read_insights',
            'response_type' => 'code',
            'state' => $state,
        ]);

        return "https://www.facebook.com/{$this->apiVersion}/dialog/oauth?{$params}";
    }

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::get("{$this->baseUrl}/oauth/access_token", [
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'redirect_uri' => config('services.meta.redirect_uri'),
            'code' => $code,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to exchange code for token: ' . $response->body());
        }

        $data = $response->json();

        // Exchange for long-lived token
        return $this->getLongLivedToken($data['access_token']);
    }

    /**
     * Get long-lived access token (60 days).
     */
    public function getLongLivedToken(string $shortLivedToken): array
    {
        $response = Http::get("{$this->baseUrl}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get long-lived token: ' . $response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'token_type' => $data['token_type'] ?? 'bearer',
            'expires_in' => $data['expires_in'] ?? 5184000, // 60 days default
        ];
    }

    /**
     * Refresh access token before expiry.
     */
    public function refreshToken(AdAccount $adAccount): void
    {
        try {
            $tokenData = $this->getLongLivedToken($adAccount->access_token);

            $adAccount->update([
                'access_token' => $tokenData['access_token'],
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]);
        } catch (\Exception $e) {
            Log::error("Meta token refresh failed for ad account {$adAccount->id}: " . $e->getMessage());
            $adAccount->update(['status' => 'error']);
            throw $e;
        }
    }

    /**
     * Get ad accounts for the authenticated user.
     */
    public function getAdAccounts(string $accessToken): array
    {
        $response = Http::get("{$this->baseUrl}/me/adaccounts", [
            'access_token' => $accessToken,
            'fields' => 'id,name,account_id,currency,timezone_name,account_status',
            'limit' => 100,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch ad accounts: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Get campaigns for an ad account.
     */
    public function getCampaigns(AdAccount $adAccount): array
    {
        $this->ensureValidToken($adAccount);

        $response = Http::get("{$this->baseUrl}/act_{$adAccount->platform_account_id}/campaigns", [
            'access_token' => $adAccount->access_token,
            'fields' => 'id,name,status,objective,daily_budget,lifetime_budget,start_time,stop_time,created_time,updated_time',
            'limit' => 500,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch campaigns: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Get ad sets for a campaign.
     */
    public function getAdSets(AdAccount $adAccount, string $campaignId): array
    {
        $this->ensureValidToken($adAccount);

        $response = Http::get("{$this->baseUrl}/{$campaignId}/adsets", [
            'access_token' => $adAccount->access_token,
            'fields' => 'id,name,status,daily_budget,targeting,optimization_goal',
            'limit' => 500,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch ad sets: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Get ads for an ad set.
     */
    public function getAds(AdAccount $adAccount, string $adSetId): array
    {
        $this->ensureValidToken($adAccount);

        $response = Http::get("{$this->baseUrl}/{$adSetId}/ads", [
            'access_token' => $adAccount->access_token,
            'fields' => 'id,name,status,creative{title,body,image_url,video_id,call_to_action_type,thumbnail_url}',
            'limit' => 500,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch ads: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Get campaign insights (metrics).
     */
    public function getCampaignInsights(AdAccount $adAccount, string $campaignId, string $dateStart, string $dateEnd): array
    {
        $this->ensureValidToken($adAccount);

        $response = Http::get("{$this->baseUrl}/{$campaignId}/insights", [
            'access_token' => $adAccount->access_token,
            'fields' => 'impressions,clicks,spend,actions,action_values,cpc,cpm,ctr,reach,frequency',
            'time_range' => json_encode(['since' => $dateStart, 'until' => $dateEnd]),
            'time_increment' => 1, // daily breakdown
            'limit' => 100,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch insights: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Update campaign status (pause/activate).
     */
    public function updateCampaignStatus(AdAccount $adAccount, string $campaignId, string $status): array
    {
        $this->ensureValidToken($adAccount);

        $response = Http::post("{$this->baseUrl}/{$campaignId}", [
            'access_token' => $adAccount->access_token,
            'status' => strtoupper($status), // ACTIVE, PAUSED
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to update campaign status: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Update campaign budget.
     */
    public function updateCampaignBudget(AdAccount $adAccount, string $campaignId, float $dailyBudget): array
    {
        $this->ensureValidToken($adAccount);

        // Meta API expects budget in cents
        $budgetInCents = (int) ($dailyBudget * 100);

        $response = Http::post("{$this->baseUrl}/{$campaignId}", [
            'access_token' => $adAccount->access_token,
            'daily_budget' => $budgetInCents,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to update campaign budget: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get custom audiences.
     */
    public function getCustomAudiences(AdAccount $adAccount): array
    {
        $this->ensureValidToken($adAccount);

        $response = Http::get("{$this->baseUrl}/act_{$adAccount->platform_account_id}/customaudiences", [
            'access_token' => $adAccount->access_token,
            'fields' => 'id,name,subtype,approximate_count,delivery_status,operation_status',
            'limit' => 500,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch audiences: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Get pixels.
     */
    public function getPixels(AdAccount $adAccount): array
    {
        $this->ensureValidToken($adAccount);

        $response = Http::get("{$this->baseUrl}/act_{$adAccount->platform_account_id}/adspixels", [
            'access_token' => $adAccount->access_token,
            'fields' => 'id,name,code,last_fired_time,is_created_by_app',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch pixels: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Ensure the ad account has a valid token.
     */
    private function ensureValidToken(AdAccount $adAccount): void
    {
        if ($adAccount->isTokenExpired()) {
            $this->refreshToken($adAccount);
        }
    }
}
