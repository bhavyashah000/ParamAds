<?php

namespace App\Modules\AudienceIntelligence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audience extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'ad_account_id', 'platform', 'platform_audience_id',
        'name', 'type', 'subtype', 'size', 'status', 'source',
        'targeting_spec', 'platform_data', 'last_synced_at',
    ];

    protected $casts = [
        'targeting_spec' => 'array',
        'platform_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(\App\Modules\AdAccounts\Models\AdAccount::class);
    }

    public function overlaps()
    {
        return $this->hasMany(AudienceOverlap::class, 'audience_a_id');
    }
}
