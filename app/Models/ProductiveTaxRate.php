<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveTaxRate extends Model
{
    protected $table = 'productive_tax_rates';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'primary_component_name',
        'primary_component_value',
        'secondary_component_name',
        'secondary_component_value',
        'archived_at',
        
        'subsidiary_id'
    ];

    protected $casts = [
        'primary_component_value' => 'decimal:2',
        'secondary_component_value' => 'decimal:2',
        'archived_at' => 'timestamp',
    ];

    /**
     * Get the subsidiary that owns the tax rate.
     */
    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'subsidiary_id');
    }
}
