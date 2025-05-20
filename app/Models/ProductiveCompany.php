<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductiveCompany extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Disable Laravel timestamps
    protected $fillable = [
        'id',
        'type',
        'name',
        'billing_name',
        'vat',
        'default_currency',
        'created_at',
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
        'organization_type',
        'organization_id',
        'default_subsidiary_meta_included',
        'default_tax_rate_meta_included',
        'custom_field_people_meta_included',
        'custom_field_attachments_meta_included',
        'productive_created_at',
        'productive_updated_at',
    ];    protected $casts = [
        'created_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'archived_at' => 'datetime',
        'invoice_email_recipients' => 'array',
        'custom_fields' => 'array',
        'projectless_budgets' => 'boolean',
        'tag_list' => 'array',
        'contact' => 'array',
        'sample_data' => 'boolean',
        'settings' => 'array',
        'external_sync' => 'array',
        'organization_type' => 'string',
        'organization_id' => 'string',
        'default_subsidiary_meta' => 'array',
        'default_tax_rate_meta' => 'array',
        'custom_field_people_meta' => 'array',
        'custom_field_attachments_meta' => 'array',
        'productive_created_at' => 'datetime',
        'productive_updated_at' => 'datetime',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(ProductiveProject::class, 'company_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(ProductiveDeal::class, 'company_id');
    }
}
