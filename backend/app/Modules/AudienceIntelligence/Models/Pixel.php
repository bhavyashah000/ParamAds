<?php

namespace App\Modules\AudienceIntelligence\Models;

use Illuminate\Database\Eloquent\Model;

class Pixel extends Model
{
    protected $fillable = [
        'organization_id', 'ad_account_id', 'platform', 'platform_pixel_id',
        'name', 'status', 'last_fired_at', 'events_tracked', 'platform_data',
    ];

    protected $casts = [
        'events_tracked' => 'array',
        'platform_data' => 'array',
        'last_fired_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(\App\Modules\AdAccounts\Models\AdAccount::class);
    }
}
