<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveCompany extends Model
{
    use SoftDeletes;

    protected $table = 'productive_companies';

    public $incrementing = false;
    public $timestamps = false; // Disable Laravel timestamps

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'type',
        'name',
        'billing_name',
        'vat',
        'default_currency',
        'created_at_api',
        'last_activity_at',
        'archived_at',
        'avatar_url',
        'invoice_email_recipients',
        'custom_fields',
        'company_code',
        'domain',
        'projectless_budgets',
        'leitweg_id',
        'buyer_reference',
        'peppol_id',
        'default_subsidiary_id',
        'default_tax_rate_id',
        'default_document_type_id',
        'description',
        'due_days',
        'tag_list',
        'contact',
        'sample_data',
        'settings',
        'external_id',
        'external_sync',
        'custom_field_people',
        'custom_field_attachments',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'projectless_budgets' => 'boolean',
        'sample_data' => 'boolean',
        'external_sync' => 'boolean',
        'created_at_api' => 'datetime',
        'last_activity_at' => 'datetime',
        'archived_at' => 'datetime',
        'invoice_email_recipients' => 'array',
        'custom_fields' => 'array',
        'tag_list' => 'array',
        'contact' => 'array',
        'settings' => 'array',
        'custom_field_people' => 'array',
        'custom_field_attachments' => 'array',
        'due_days' => 'integer',
    ];

    /**
     * Get the subsidiary associated with the company.
     */
    public function subsidiary()
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'default_subsidiary_id');
    }
    /**
     * Get the tax rate associated with the company.
     */

    public function taxRate()
    {
        return $this->belongsTo(ProductiveTaxRate::class, 'default_tax_rate_id');
    }

}
