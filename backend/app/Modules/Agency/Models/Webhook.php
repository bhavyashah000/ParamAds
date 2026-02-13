<?php

namespace App\Modules\Agency\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'url', 'secret',
        'events', 'active', 'failure_count',
        'last_triggered_at', 'last_failed_at',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'last_failed_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(WebhookLog::class);
    }
}
