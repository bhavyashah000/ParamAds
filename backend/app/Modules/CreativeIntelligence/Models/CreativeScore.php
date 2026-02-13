<?php

namespace App\Modules\CreativeIntelligence\Models;

use Illuminate\Database\Eloquent\Model;

class CreativeScore extends Model
{
    protected $fillable = [
        'organization_id', 'creative_id', 'date', 'overall_score',
        'engagement_score', 'conversion_score', 'relevance_score', 'fatigue_score',
        'impressions', 'clicks', 'conversions', 'spend', 'ctr', 'cvr', 'cpa',
        'scoring_data',
    ];

    protected $casts = [
        'date' => 'date',
        'scoring_data' => 'array',
    ];

    public function creative()
    {
        return $this->belongsTo(Creative::class);
    }
}
