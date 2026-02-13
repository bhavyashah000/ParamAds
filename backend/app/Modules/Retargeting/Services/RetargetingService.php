<?php

namespace App\Modules\Retargeting\Services;

use App\Modules\Retargeting\Models\RetargetingFunnel;

class RetargetingService
{
    /**
     * Create a retargeting funnel.
     *
     * Stages format:
     * [
     *   {"name": "Awareness", "audience_type": "website_visitors", "lookback_days": 30, "exclusions": []},
     *   {"name": "Consideration", "audience_type": "engaged_visitors", "lookback_days": 14, "exclusions": ["purchasers"]},
     *   {"name": "Conversion", "audience_type": "add_to_cart", "lookback_days": 7, "exclusions": ["purchasers"]},
     *   {"name": "Retention", "audience_type": "purchasers", "lookback_days": 90, "exclusions": []}
     * ]
     */
    public function createFunnel(int $orgId, array $data): RetargetingFunnel
    {
        return RetargetingFunnel::create([
            'organization_id' => $orgId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'platform' => $data['platform'],
            'stages' => $data['stages'],
            'pixel_id' => $data['pixel_id'] ?? null,
            'settings' => $data['settings'] ?? [],
            'status' => 'active',
        ]);
    }

    /**
     * Update funnel.
     */
    public function updateFunnel(RetargetingFunnel $funnel, array $data): RetargetingFunnel
    {
        $funnel->update($data);
        return $funnel->fresh();
    }

    /**
     * Get funnel performance.
     */
    public function getFunnelPerformance(RetargetingFunnel $funnel): array
    {
        // In production, aggregate metrics per stage
        $stages = $funnel->stages ?? [];
        $performance = [];

        foreach ($stages as $index => $stage) {
            $performance[] = [
                'stage' => $stage['name'],
                'order' => $index + 1,
                'audience_size' => $stage['estimated_size'] ?? 0,
                'impressions' => 0,
                'clicks' => 0,
                'conversions' => 0,
                'spend' => 0,
                'cpa' => 0,
                'drop_off_rate' => 0,
            ];
        }

        return $performance;
    }

    /**
     * Get recommended funnel template.
     */
    public function getTemplates(): array
    {
        return [
            [
                'name' => 'E-commerce Standard',
                'description' => 'Standard 4-stage e-commerce retargeting funnel',
                'stages' => [
                    ['name' => 'All Visitors', 'audience_type' => 'website_visitors', 'lookback_days' => 30],
                    ['name' => 'Product Viewers', 'audience_type' => 'product_viewers', 'lookback_days' => 14],
                    ['name' => 'Cart Abandoners', 'audience_type' => 'add_to_cart', 'lookback_days' => 7],
                    ['name' => 'Past Purchasers', 'audience_type' => 'purchasers', 'lookback_days' => 180],
                ],
            ],
            [
                'name' => 'Lead Generation',
                'description' => 'B2B lead generation funnel',
                'stages' => [
                    ['name' => 'Website Visitors', 'audience_type' => 'website_visitors', 'lookback_days' => 30],
                    ['name' => 'Content Engagers', 'audience_type' => 'page_engagers', 'lookback_days' => 14],
                    ['name' => 'Form Starters', 'audience_type' => 'form_started', 'lookback_days' => 7],
                    ['name' => 'Leads', 'audience_type' => 'leads', 'lookback_days' => 90],
                ],
            ],
            [
                'name' => 'App Install',
                'description' => 'Mobile app install and engagement funnel',
                'stages' => [
                    ['name' => 'Website Visitors', 'audience_type' => 'website_visitors', 'lookback_days' => 30],
                    ['name' => 'App Page Visitors', 'audience_type' => 'app_page_visitors', 'lookback_days' => 14],
                    ['name' => 'App Installers', 'audience_type' => 'app_installers', 'lookback_days' => 90],
                    ['name' => 'Active Users', 'audience_type' => 'app_active_users', 'lookback_days' => 30],
                ],
            ],
        ];
    }
}
