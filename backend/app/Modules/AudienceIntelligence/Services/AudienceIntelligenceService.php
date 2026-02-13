<?php

namespace App\Modules\AudienceIntelligence\Services;

use App\Modules\AdAccounts\Models\AdAccount;
use App\Modules\AdAccounts\Services\MetaAdsService;
use App\Modules\AudienceIntelligence\Models\Audience;
use App\Modules\AudienceIntelligence\Models\AudienceOverlap;
use App\Modules\AudienceIntelligence\Models\Pixel;
use Illuminate\Support\Facades\Log;

class AudienceIntelligenceService
{
    public function __construct(
        private MetaAdsService $metaService
    ) {}

    /**
     * Sync audiences from platform.
     */
    public function syncAudiences(AdAccount $adAccount): void
    {
        if ($adAccount->isMeta()) {
            $this->syncMetaAudiences($adAccount);
        }
    }

    /**
     * Sync Meta custom audiences.
     */
    private function syncMetaAudiences(AdAccount $adAccount): void
    {
        try {
            $audiences = $this->metaService->getCustomAudiences($adAccount);

            foreach ($audiences as $audienceData) {
                Audience::updateOrCreate(
                    [
                        'ad_account_id' => $adAccount->id,
                        'platform_audience_id' => $audienceData['id'],
                    ],
                    [
                        'organization_id' => $adAccount->organization_id,
                        'platform' => 'meta',
                        'name' => $audienceData['name'],
                        'type' => 'custom',
                        'subtype' => $audienceData['subtype'] ?? null,
                        'size' => $audienceData['approximate_count'] ?? 0,
                        'status' => $audienceData['delivery_status']['status'] ?? 'unknown',
                        'platform_data' => $audienceData,
                        'last_synced_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("Audience sync failed for account {$adAccount->id}: " . $e->getMessage());
        }
    }

    /**
     * Sync pixels from platform.
     */
    public function syncPixels(AdAccount $adAccount): void
    {
        if (!$adAccount->isMeta()) return;

        try {
            $pixels = $this->metaService->getPixels($adAccount);

            foreach ($pixels as $pixelData) {
                Pixel::updateOrCreate(
                    [
                        'ad_account_id' => $adAccount->id,
                        'platform_pixel_id' => $pixelData['id'],
                    ],
                    [
                        'organization_id' => $adAccount->organization_id,
                        'platform' => 'meta',
                        'name' => $pixelData['name'],
                        'status' => 'active',
                        'last_fired_at' => $pixelData['last_fired_time'] ?? null,
                        'platform_data' => $pixelData,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("Pixel sync failed for account {$adAccount->id}: " . $e->getMessage());
        }
    }

    /**
     * Calculate audience overlap between two audiences.
     */
    public function calculateOverlap(Audience $audienceA, Audience $audienceB): ?AudienceOverlap
    {
        // In production, this would call the Meta Audience Overlap API
        // For now, estimate based on targeting specs
        $overlapPercent = $this->estimateOverlap($audienceA, $audienceB);

        return AudienceOverlap::updateOrCreate(
            [
                'audience_a_id' => $audienceA->id,
                'audience_b_id' => $audienceB->id,
            ],
            [
                'organization_id' => $audienceA->organization_id,
                'overlap_percentage' => $overlapPercent,
                'overlap_size' => (int) (min($audienceA->size, $audienceB->size) * ($overlapPercent / 100)),
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Get audience insights.
     */
    public function getAudienceInsights(int $orgId): array
    {
        $audiences = Audience::where('organization_id', $orgId)->get();

        return [
            'total_audiences' => $audiences->count(),
            'total_reach' => $audiences->sum('size'),
            'by_type' => $audiences->groupBy('type')->map->count(),
            'by_platform' => $audiences->groupBy('platform')->map->count(),
            'high_overlap_pairs' => AudienceOverlap::where('organization_id', $orgId)
                ->where('overlap_percentage', '>', 30)
                ->with(['audienceA', 'audienceB'])
                ->orderByDesc('overlap_percentage')
                ->limit(10)
                ->get(),
        ];
    }

    private function estimateOverlap(Audience $a, Audience $b): float
    {
        // Simple heuristic - in production use platform API
        if ($a->platform !== $b->platform) return 0;
        if ($a->type === $b->type && $a->subtype === $b->subtype) return 40;
        return 15;
    }
}
