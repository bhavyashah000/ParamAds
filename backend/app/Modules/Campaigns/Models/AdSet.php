<?php

namespace App\Modules\Campaigns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdSet extends Model
{
    use SoftDeletes;

    protected $table = 'ad_sets';

    protected $fillable = [
        'organization_id', 'campaign_id', 'platform', 'platform_adset_id',
        'name', 'status', 'daily_budget', 'targeting', 'platform_data', 'last_synced_at',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'targeting' => 'array',
        'platform_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }
}
