<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\Models\SubAccount;
use App\Modules\Agency\Models\WhiteLabelSetting;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Support\Facades\DB;

class AgencyService
{
    /**
     * Create a sub-account under the parent organization.
     */
    public function createSubAccount(int $parentOrgId, array $data): SubAccount
    {
        return DB::transaction(function () use ($parentOrgId, $data) {
            // Create child organization
            $childOrg = Organization::create([
                'name' => $data['name'],
                'slug' => \Str::slug($data['name']) . '-' . uniqid(),
                'plan' => 'agency_sub',
                'settings' => $data['settings'] ?? [],
            ]);

            // Create sub-account link
            return SubAccount::create([
                'parent_organization_id' => $parentOrgId,
                'child_organization_id' => $childOrg->id,
                'label' => $data['label'] ?? $data['name'],
                'permissions' => $data['permissions'] ?? ['view', 'manage_campaigns', 'manage_creatives'],
                'status' => 'active',
            ]);
        });
    }

    /**
     * List sub-accounts for a parent organization.
     */
    public function listSubAccounts(int $parentOrgId): array
    {
        return SubAccount::where('parent_organization_id', $parentOrgId)
            ->with('child')
            ->get()
            ->toArray();
    }

    /**
     * Get aggregated metrics across all sub-accounts.
     */
    public function getAggregatedMetrics(int $parentOrgId, string $dateFrom, string $dateTo): array
    {
        $subAccountIds = SubAccount::where('parent_organization_id', $parentOrgId)
            ->pluck('child_organization_id');

        $metrics = \App\Modules\Metrics\Models\CampaignMetric::whereHas('campaign', function ($q) use ($subAccountIds) {
            $q->whereIn('organization_id', $subAccountIds);
        })
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->selectRaw('
            SUM(spend) as total_spend,
            SUM(impressions) as total_impressions,
            SUM(clicks) as total_clicks,
            SUM(conversions) as total_conversions,
            SUM(revenue) as total_revenue
        ')
        ->first();

        return [
            'sub_account_count' => $subAccountIds->count(),
            'total_spend' => round($metrics->total_spend ?? 0, 2),
            'total_impressions' => $metrics->total_impressions ?? 0,
            'total_clicks' => $metrics->total_clicks ?? 0,
            'total_conversions' => $metrics->total_conversions ?? 0,
            'total_revenue' => round($metrics->total_revenue ?? 0, 2),
            'roas' => ($metrics->total_spend ?? 0) > 0
                ? round(($metrics->total_revenue ?? 0) / $metrics->total_spend, 4) : 0,
        ];
    }

    /**
     * Update white-label settings.
     */
    public function updateWhiteLabel(int $orgId, array $data): WhiteLabelSetting
    {
        return WhiteLabelSetting::updateOrCreate(
            ['organization_id' => $orgId],
            $data
        );
    }

    /**
     * Get white-label settings.
     */
    public function getWhiteLabel(int $orgId): ?WhiteLabelSetting
    {
        return WhiteLabelSetting::where('organization_id', $orgId)->first();
    }
}
