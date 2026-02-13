<?php

namespace App\Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    protected $fillable = [
        'organization_id', 'created_by', 'name', 'type', 'frequency',
        'format', 'filters', 'recipients', 'sections', 'status',
        'last_sent_at', 'next_send_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'recipients' => 'array',
        'sections' => 'array',
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime',
    ];

    public function history()
    {
        return $this->hasMany(ReportHistory::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
