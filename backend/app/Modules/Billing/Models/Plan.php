<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'stripe_price_id', 'price', 'interval',
        'features', 'max_ad_accounts', 'max_campaigns', 'max_automation_rules',
        'max_team_members', 'has_ai_features', 'has_creative_intelligence',
        'has_audience_intelligence', 'has_agency_features', 'has_api_access',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'has_ai_features' => 'boolean',
        'has_creative_intelligence' => 'boolean',
        'has_audience_intelligence' => 'boolean',
        'has_agency_features' => 'boolean',
        'has_api_access' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
