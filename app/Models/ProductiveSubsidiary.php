<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductiveSubsidiary extends Model
{
    use SoftDeletes;

    protected $table = 'productive_subsidiaries';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Disable Laravel timestamps

    protected $fillable = [
        'id',
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
        'bill_from_id',
        'custom_domain_id',
        'default_tax_rate_id',
        'integration_id'
    ];

    protected $nullable = [
        'invoice_number_format',
        'invoice_number_scope',
        'archived_at',
        'einvoice_payment_means_type_id',
        'einvoice_download_format_id',
        'peppol_id',
        'export_integration_type_id',
        'invoice_logo_url',
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
     * Get the bill_from_id (contact_entries) associated with the subsidiary.
     */
    public function billFrom(): BelongsTo
    {
        return $this->belongsTo(ProductiveContactEntry::class, 'bill_from_id');
    }

    /**
     * Get the custom_domain associated with the subsidiary.
     */
    public function customDomain(): BelongsTo
    {
        return $this->belongsTo(ProductiveCustomDomain::class, 'custom_domain_id');
    }

    /**
     * Get the default_tax_rate associated with the subsidiary.
     */
    public function defaultTaxRate(): BelongsTo
    {
        return $this->belongsTo(ProductiveTaxRate::class, 'default_tax_rate_id');
    }

    /**
     * Get the integration associated with the subsidiary.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(ProductiveIntegration::class, 'integration_id');
    }

}
