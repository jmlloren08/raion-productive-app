<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveAttachment extends Model
{
    protected $table = 'productive_attachments';

    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'content_type',
        'size',
        'url',
        'thumb',
        'temp_url',
        'resized',
        'created_at_api',
        'deleted_at_api',
        'attachment_type',
        'message_id',
        'external_id',
        'attachable_type',
        // Relationships
        'creator_id',
        'invoice_id',
        'purchase_order_id',
        'bill_id',
        'email_id',
        'page_id',
        'expense_id',
        'comment_id',
        'task_id',
        'document_style_id',
        'document_type_id',
        'deal_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'size' => 'integer',
        'resized' => 'boolean',
        'created_at_api' => 'timestamp',
        'deleted_at_api' => 'timestamp',
    ];

    /**
     * Get the creator of the attachment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the invoice associated with the attachment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ProductiveInvoice::class, 'invoice_id');
    }

    /**
     * Get the purchase order associated with the attachment.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(ProductivePurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Get the bill associated with the attachment.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(ProductiveBill::class, 'bill_id');
    }

    /**
     * Get the email associated with the attachment.
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(ProductiveEmail::class, 'email_id');
    }

    /**
     * Get the page associated with the attachment.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(ProductivePage::class, 'page_id');
    }

    /**
     * Get the expense associated with the attachment.
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(ProductiveExpense::class, 'expense_id');
    }

    /**
     * Get the comment associated with the attachment.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(ProductiveComment::class, 'comment_id');
    }

    /**
     * Get the task associated with the attachment.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductiveTask::class, 'task_id');
    }

    /**
     * Get the document style associated with the attachment.
     */
    public function documentStyle(): BelongsTo
    {
        return $this->belongsTo(ProductiveDocumentStyle::class, 'document_style_id');
    }

    /**
     * Get the document type associated with the attachment.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(ProductiveDocumentType::class, 'document_type_id');
    }

    /**
     * Get the deal associated with the attachment.
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }
}
