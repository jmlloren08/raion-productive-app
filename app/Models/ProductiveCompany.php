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
        'created_at_api',
        'last_activity_at',
        'archived_at',
        'avatar_url',
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
        'invoice_email_recipients',
        'settings',
        'custom_fields',
        'external_id',
        'external_sync',
        'productive_id',
    ];

    protected $casts = [
        'tag_list' => 'array',
        'invoice_email_recipients' => 'array',
        'custom_fields' => 'array',
        'contact' => 'array',
        'settings' => 'array',
        'created_at_api' => 'datetime',
        'last_activity_at' => 'datetime',
        'archived_at' => 'datetime',
        'projectless_budgets' => 'boolean',
        'external_sync' => 'boolean',
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
