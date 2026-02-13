<?php

namespace App\Modules\CreativeIntelligence\Models;

use Illuminate\Database\Eloquent\Model;

class CreativeFatigue extends Model
{
    protected $table = 'creative_fatigue';

    protected $fillable = [
        'organization_id', 'creative_id', 'date', 'fatigue_level',
        'frequency', 'ctr_trend', 'cvr_trend', 'cpa_trend',
        'days_running', 'estimated_days_remaining', 'recommendation',
    ];

    protected $casts = [
        'date' => 'date',
        'ctr_trend' => 'array',
        'cvr_trend' => 'array',
        'cpa_trend' => 'array',
    ];

    public function creative()
    {
        return $this->belongsTo(Creative::class);
    }
}
