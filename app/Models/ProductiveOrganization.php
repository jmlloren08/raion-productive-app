<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductiveOrganization extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name'
    ];

    /**
     * Get all of the companies for the organization.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(ProductiveCompany::class, 'organization_id');
    }

    /**
     * Get all of the projects for the organization.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(ProductiveProject::class, 'organization_id');
    }

    /**
     * Get all of the time entries for the organization.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(ProductiveTimeEntries::class, 'organization_id');
    }

    /**
     * Get all of the time entry versions for the organization.
     */
    public function timeEntryVersions(): HasMany
    {
        return $this->hasMany(ProductiveTimeEntryVersions::class, 'organization_id');
    }
}
