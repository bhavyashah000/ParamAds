<?php

namespace App\Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

class Organization extends Model
{
    use HasFactory, SoftDeletes, Billable;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'website', 'logo',
        'timezone', 'currency', 'plan', 'settings', 'features',
        'is_active', 'is_agency', 'parent_organization_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_agency' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function owner()
    {
        return $this->hasOne(\App\Models\User::class)->where('role', 'owner');
    }

    public function adAccounts()
    {
        return $this->hasMany(\App\Modules\AdAccounts\Models\AdAccount::class);
    }

    public function campaigns()
    {
        return $this->hasMany(\App\Modules\Campaigns\Models\Campaign::class);
    }

    public function automationRules()
    {
        return $this->hasMany(\App\Modules\Automation\Models\AutomationRule::class);
    }

    public function creatives()
    {
        return $this->hasMany(\App\Modules\CreativeIntelligence\Models\Creative::class);
    }

    public function audiences()
    {
        return $this->hasMany(\App\Modules\AudienceIntelligence\Models\Audience::class);
    }

    public function aiInsights()
    {
        return $this->hasMany(\App\Modules\AIEngine\Models\AIInsight::class);
    }

    public function reports()
    {
        return $this->hasMany(\App\Modules\Reporting\Models\Report::class);
    }

    public function alerts()
    {
        return $this->hasMany(\App\Modules\AlertSystem\Models\Alert::class);
    }

    public function parentOrganization()
    {
        return $this->belongsTo(Organization::class, 'parent_organization_id');
    }

    public function childOrganizations()
    {
        return $this->hasMany(Organization::class, 'parent_organization_id');
    }

    public function webhooks()
    {
        return $this->hasMany(\App\Modules\AgencyManagement\Models\Webhook::class);
    }

    // Helpers
    public function isOnPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function isAgency(): bool
    {
        return $this->is_agency;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAgencies($query)
    {
        return $query->where('is_agency', true);
    }
}
