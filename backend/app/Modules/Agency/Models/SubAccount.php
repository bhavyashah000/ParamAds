<?php

namespace App\Modules\Agency\Models;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class SubAccount extends Model
{
    protected $fillable = [
        'parent_organization_id', 'child_organization_id',
        'label', 'permissions', 'status',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(Organization::class, 'parent_organization_id');
    }

    public function child()
    {
        return $this->belongsTo(Organization::class, 'child_organization_id');
    }
}
