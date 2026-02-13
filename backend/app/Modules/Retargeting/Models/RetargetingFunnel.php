<?php

namespace App\Modules\Retargeting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetargetingFunnel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'name', 'description', 'status', 'platform',
        'stages', 'pixel_id', 'settings', 'performance_data',
    ];

    protected $casts = [
        'stages' => 'array',
        'settings' => 'array',
        'performance_data' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }

    public function pixel()
    {
        return $this->belongsTo(\App\Modules\AudienceIntelligence\Models\Pixel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
