<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductiveSubsidiary extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'invoice_number_format',
        'invoice_number_scope',
        'archived_at',
        'show_delivery_date',
        'einvoice_payment_means_type_id',
        'einvoice_download_format_id',
        'peppol_id',
        'export_integration_type_id',
        'invoice_logo_url',
        'organization_id',
        'bill_from_id',
        'custom_domain_id',
        'default_tax_rate_id',
        'integration_id'
    ];

    protected $casts = [
        'archived_at' => 'timestamp',
        'show_delivery_date' => 'boolean'
    ];

    /**
     * Get the organization that owns the subsidiary.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(ProductiveOrganization::class, 'organization_id');
    }

    /**
     * Get the default tax rate for this subsidiary.
     */
    public function defaultTaxRate(): BelongsTo
    {
        return $this->belongsTo(ProductiveTaxRate::class, 'default_tax_rate_id');
    }

    /**
     * Get the companies that use this subsidiary.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(ProductiveCompany::class, 'default_subsidiary_id');
    }
}
