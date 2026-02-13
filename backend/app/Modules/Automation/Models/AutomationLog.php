<?php

namespace App\Modules\Automation\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationLog extends Model
{
    protected $fillable = [
        'organization_id', 'automation_rule_id', 'target_type', 'target_id',
        'action_type', 'condition_snapshot', 'action_result', 'status', 'error_message',
    ];

    protected $casts = [
        'condition_snapshot' => 'array',
        'action_result' => 'array',
    ];

    public function rule()
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }
}
