<?php

namespace App\Modules\AdAccounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class AdAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'platform', 'platform_account_id', 'name',
        'currency', 'timezone', 'access_token', 'refresh_token',
        'token_expires_at', 'status', 'platform_data', 'last_synced_at',
    ];

    protected $casts = [
        'platform_data' => 'array',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    // Encrypt tokens on set
    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setRefreshTokenAttribute($value)
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    // Relationships
    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }

    public function campaigns()
    {
        return $this->hasMany(\App\Modules\Campaigns\Models\Campaign::class);
    }

    public function metrics()
    {
        return $this->hasMany(\App\Modules\Metrics\Models\CampaignMetric::class);
    }

    // Helpers
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function isMeta(): bool
    {
        return $this->platform === 'meta';
    }

    public function isGoogle(): bool
    {
        return $this->platform === 'google';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
