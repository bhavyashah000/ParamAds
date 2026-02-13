<?php

namespace App\Modules\AudienceIntelligence\Models;

use Illuminate\Database\Eloquent\Model;

class AudienceOverlap extends Model
{
    protected $table = 'audience_overlaps';

    protected $fillable = [
        'organization_id', 'audience_a_id', 'audience_b_id',
        'overlap_percentage', 'overlap_size', 'calculated_at',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
    ];

    public function audienceA()
    {
        return $this->belongsTo(Audience::class, 'audience_a_id');
    }

    public function audienceB()
    {
        return $this->belongsTo(Audience::class, 'audience_b_id');
    }
}
