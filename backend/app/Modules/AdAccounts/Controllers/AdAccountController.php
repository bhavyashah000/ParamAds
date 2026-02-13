<?php

namespace App\Modules\AdAccounts\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdAccounts\Models\AdAccount;
use App\Modules\AdAccounts\Services\MetaAdsService;
use App\Modules\AdAccounts\Services\GoogleAdsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdAccountController extends Controller
{
    public function __construct(
        private MetaAdsService $metaService,
        private GoogleAdsService $googleService
    ) {}

    /**
     * List ad accounts for the organization.
     */
    public function index(Request $request): JsonResponse
    {
        $accounts = AdAccount::where('organization_id', $request->user()->organization_id)
            ->withCount('campaigns')
            ->get();

        return response()->json(['data' => $accounts]);
    }

    /**
     * Get single ad account.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $account = AdAccount::where('organization_id', $request->user()->organization_id)
            ->with('campaigns')
            ->findOrFail($id);

        return response()->json(['data' => $account]);
    }

    /**
     * Initiate OAuth connection for a platform.
     */
    public function connect(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'required|in:meta,google',
        ]);

        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $url = match ($request->platform) {
            'meta' => $this->metaService->getAuthorizationUrl($state),
            'google' => $this->googleService->getAuthorizationUrl($state),
        };

        return response()->json([
            'authorization_url' => $url,
            'state' => $state,
        ]);
    }

    /**
     * Handle OAuth callback for Meta.
     */
    public function metaCallback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $tokenData = $this->metaService->exchangeCodeForToken($request->code);
        $adAccounts = $this->metaService->getAdAccounts($tokenData['access_token']);

        $connected = [];
        foreach ($adAccounts as $account) {
            $adAccount = AdAccount::updateOrCreate(
                [
                    'organization_id' => $request->user()->organization_id,
                    'platform' => 'meta',
                    'platform_account_id' => $account['account_id'],
                ],
                [
                    'name' => $account['name'],
                    'currency' => $account['currency'] ?? 'USD',
                    'timezone' => $account['timezone_name'] ?? 'UTC',
                    'access_token' => $tokenData['access_token'],
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 5184000),
                    'status' => 'active',
                    'platform_data' => $account,
                ]
            );
            $connected[] = $adAccount;
        }

        return response()->json([
            'message' => count($connected) . ' Meta ad account(s) connected.',
            'data' => $connected,
        ]);
    }

    /**
     * Handle OAuth callback for Google.
     */
    public function googleCallback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $tokenData = $this->googleService->exchangeCodeForToken($request->code);

        $customers = $this->googleService->getAccessibleCustomers($tokenData['access_token']);

        $connected = [];
        foreach ($customers as $resourceName) {
            $customerId = str_replace('customers/', '', $resourceName);

            $adAccount = AdAccount::updateOrCreate(
                [
                    'organization_id' => $request->user()->organization_id,
                    'platform' => 'google',
                    'platform_account_id' => $customerId,
                ],
                [
                    'name' => "Google Ads - {$customerId}",
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                    'status' => 'active',
                ]
            );
            $connected[] = $adAccount;
        }

        return response()->json([
            'message' => count($connected) . ' Google ad account(s) connected.',
            'data' => $connected,
        ]);
    }

    /**
     * Disconnect an ad account.
     */
    public function disconnect(Request $request, int $id): JsonResponse
    {
        $account = AdAccount::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $account->update([
            'status' => 'disconnected',
            'access_token' => null,
            'refresh_token' => null,
        ]);

        return response()->json(['message' => 'Ad account disconnected.']);
    }

    /**
     * Sync campaigns from the platform.
     */
    public function syncCampaigns(Request $request, int $id): JsonResponse
    {
        $account = AdAccount::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        // Dispatch sync job
        \App\Modules\Campaigns\Jobs\SyncCampaignsJob::dispatch($account);

        return response()->json(['message' => 'Campaign sync initiated.']);
    }
}
