<?php

namespace App\Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Model;

class ReportHistory extends Model
{
    protected $table = 'report_history';

    protected $fillable = [
        'scheduled_report_id', 'organization_id',
        'file_url', 'status', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function scheduledReport()
    {
        return $this->belongsTo(ScheduledReport::class);
    }
}
