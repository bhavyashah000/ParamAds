<?php

namespace App\Modules\Agency\Models;

use Illuminate\Database\Eloquent\Model;

class WhiteLabelSetting extends Model
{
    protected $fillable = [
        'organization_id', 'brand_name', 'logo_url', 'favicon_url',
        'primary_color', 'secondary_color', 'custom_domain',
        'support_email', 'support_url', 'custom_css',
        'email_templates', 'hide_powered_by',
    ];

    protected $casts = [
        'custom_css' => 'array',
        'email_templates' => 'array',
        'hide_powered_by' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Organizations\Models\Organization::class);
    }
}
