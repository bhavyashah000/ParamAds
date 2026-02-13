<?php

namespace App\Modules\Metrics\Models;

use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    protected $table = 'daily_summaries';

    protected $fillable = [
        'organization_id', 'ad_account_id', 'platform', 'date',
        'total_campaigns', 'active_campaigns', 'total_spend', 'total_revenue',
        'total_impressions', 'total_clicks', 'total_conversions',
        'avg_cpc', 'avg_cpm', 'avg_ctr', 'avg_roas', 'avg_cpa',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }
}
