<?php

namespace App\Modules\CreativeIntelligence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Creative extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'ad_id', 'campaign_id', 'platform', 'creative_type',
        'headline', 'body', 'cta', 'image_url', 'video_url', 'thumbnail_url',
        'platform_creative_id', 'platform_data', 'hash',
    ];

    protected $casts = [
        'platform_data' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }

    public function ad()
    {
        return $this->belongsTo(\App\Modules\Campaigns\Models\Ad::class);
    }

    public function scores()
    {
        return $this->hasMany(CreativeScore::class);
    }

    public function latestScore()
    {
        return $this->hasOne(CreativeScore::class)->latestOfMany();
    }

    public function fatigueMetrics()
    {
        return $this->hasMany(CreativeFatigue::class);
    }
}
