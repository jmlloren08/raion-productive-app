<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductiveDocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'tax1_name',
        'tax1_value',
        'tax2_name',
        'tax2_value',
        'locale',
        'document_template_id',
        'exportable_type_id',
        'note',
        'footer',
        'template_options',
        'archived_at',
        'header_template',
        'body_template',
        'footer_template',
        'scss_template',
        'exporter_options',
        'email_template',
        'email_subject',
        'email_data',
        'dual_currency',
        'organization_id',
        'subsidiary_id',
        'document_style_id',
        'attachment_id'
    ];

    protected $casts = [
        'tax1_value' => 'decimal:2',
        'tax2_value' => 'decimal:2',
        'template_options' => 'json',
        'exporter_options' => 'json',
        'email_data' => 'json',
        'dual_currency' => 'boolean',
        'archived_at' => 'timestamp',
    ];

    /**
     * Get the organization that owns the document type.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(ProductiveOrganization::class, 'organization_id');
    }

    /**
     * Get the subsidiary that owns the document type.
     */
    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'subsidiary_id');
    }

    /**
     * Get the companies that use this document type as default.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(ProductiveCompany::class, 'default_document_type_id');
    }
}
