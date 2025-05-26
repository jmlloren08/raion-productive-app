<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductiveTaxRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'primary_component_name',
        'primary_component_value',
        'secondary_component_name',
        'secondary_component_value',
        'archived_at',
        'organization_id',
        'subsidiary_id'
    ];

    protected $casts = [
        'primary_component_value' => 'decimal:2',
        'secondary_component_value' => 'decimal:2',
        'archived_at' => 'timestamp',
    ];

    /**
     * Get the organization that owns the tax rate.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(ProductiveOrganization::class, 'organization_id');
    }

    /**
     * Get the subsidiary that owns the tax rate.
     */
    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'subsidiary_id');
    }

    /**
     * Get the companies that use this tax rate as default.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(ProductiveCompany::class, 'default_tax_rate_id');
    }
}
