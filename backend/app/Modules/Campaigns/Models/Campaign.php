<?php

namespace App\Modules\Campaigns\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'ad_account_id', 'platform', 'platform_campaign_id',
        'name', 'status', 'objective', 'daily_budget', 'lifetime_budget',
        'start_date', 'end_date', 'targeting', 'platform_data', 'last_synced_at',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'targeting' => 'array',
        'platform_data' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_synced_at' => 'datetime',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(\App\Modules\AdAccounts\Models\AdAccount::class);
    }

    public function adSets()
    {
        return $this->hasMany(AdSet::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    public function metrics()
    {
        return $this->hasMany(\App\Modules\Metrics\Models\CampaignMetric::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
