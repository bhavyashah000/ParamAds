<?php

namespace App\Modules\Metrics\Models;

use Illuminate\Database\Eloquent\Model;

class AdMetric extends Model
{
    protected $table = 'ad_metrics';

    protected $fillable = [
        'organization_id', 'ad_id', 'campaign_id', 'platform', 'date',
        'impressions', 'clicks', 'spend', 'conversions', 'revenue',
        'cpc', 'cpm', 'ctr', 'roas', 'cpa', 'extra_metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'extra_metrics' => 'array',
    ];

    public function ad()
    {
        return $this->belongsTo(\App\Modules\Campaigns\Models\Ad::class);
    }
}
