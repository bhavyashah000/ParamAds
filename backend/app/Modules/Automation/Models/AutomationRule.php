<?php

namespace App\Modules\Automation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutomationRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'name', 'description', 'status', 'scope', 'platform',
        'conditions', 'condition_logic', 'actions', 'time_window',
        'check_interval_minutes', 'cooldown_minutes', 'last_executed_at',
        'execution_count', 'target_ids', 'apply_to_all',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'target_ids' => 'array',
        'apply_to_all' => 'boolean',
        'last_executed_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }

    public function logs()
    {
        return $this->hasMany(AutomationLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isOnCooldown(): bool
    {
        if (!$this->last_executed_at) return false;
        return $this->last_executed_at->addMinutes($this->cooldown_minutes)->isFuture();
    }
}
