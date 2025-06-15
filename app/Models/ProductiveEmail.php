<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveEmail extends Model
{
    protected $table = 'productive_emails';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'subject',
        'body',
        'body_truncated',
        'auto_linked',
        'linked_type',
        'linked_id',
        'external_id',
        'dismissed_at',
        'created_at_api',
        'delivered_at',
        'received_at',
        'failed_at',
        'outgoing',
        'from',
        // Relationships
        'creator_id',
        'deal_id',
        'invoice_id',
        'prs_id',
        'attachment_id',
    ];

    protected $casts = [
        'auto_linked' => 'boolean',
        'outgoing' => 'boolean',
        'dismissed_at' => 'timestamp',
        'created_at_api' => 'timestamp',
        'delivered_at' => 'timestamp',
        'received_at' => 'timestamp',
        'failed_at' => 'timestamp',
    ];

    /**
     * Get the creator of the email.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the deal associated with the email.
     */
    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    /**
     * Get the invoice associated with the email.
     */
    public function invoice()
    {
        return $this->belongsTo(ProductiveInvoice::class, 'invoice_id');
    }

    /**
     * Get the integration associated with the email.
     */
    public function prs()
    {
        return $this->belongsTo(ProductivePrs::class, 'prs_id');
    }

    /**
     * Get the attachment associated with the email.
     */
    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }
}
