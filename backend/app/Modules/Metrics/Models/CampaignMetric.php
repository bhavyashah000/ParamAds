<?php

namespace App\Modules\Metrics\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignMetric extends Model
{
    protected $table = 'campaign_metrics';

    protected $fillable = [
        'organization_id', 'campaign_id', 'ad_account_id', 'platform', 'date',
        'impressions', 'clicks', 'spend', 'conversions', 'revenue',
        'cpc', 'cpm', 'ctr', 'roas', 'cpa', 'reach', 'frequency',
        'video_views', 'link_clicks', 'add_to_cart', 'purchases', 'extra_metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:4',
        'revenue' => 'decimal:4',
        'cpc' => 'decimal:4',
        'cpm' => 'decimal:4',
        'ctr' => 'decimal:4',
        'roas' => 'decimal:4',
        'cpa' => 'decimal:4',
        'frequency' => 'decimal:4',
        'extra_metrics' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(\App\Modules\Campaigns\Models\Campaign::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(\App\Modules\AdAccounts\Models\AdAccount::class);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }

    public function scopeForDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }
}
