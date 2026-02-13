<?php

namespace App\Modules\Campaigns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ad extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'ad_set_id', 'campaign_id', 'platform', 'platform_ad_id',
        'name', 'status', 'creative_type', 'headline', 'body', 'cta',
        'image_url', 'video_url', 'platform_data', 'last_synced_at',
    ];

    protected $casts = [
        'platform_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function adSet()
    {
        return $this->belongsTo(AdSet::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }

    public function metrics()
    {
        return $this->hasMany(\App\Modules\Metrics\Models\AdMetric::class);
    }
}
