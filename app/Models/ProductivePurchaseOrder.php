<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductivePurchaseOrder extends Model
{
    protected $table = 'productive_purchase_orders';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'subject',
        'status_id',
        'issued_on',
        'delivery_on',
        'sent_on',
        'received_on',
        'created_at_api',
        'number',
        'note',
        'note_interpolated',
        'email_key',
        'payment_status_id',
        'exchange_rate',
        'exchange_date',
        'currency',
        'currency_default',
        'currency_normalized',
        'total_cost',
        'total_cost_default',
        'total_cost_normalized',
        'total_cost_with_tax',
        'total_cost_with_tax_default',
        'total_cost_with_tax_normalized',
        'total_received',
        'total_received_default',
        'total_received_normalized',
        // Relationships
        'vendor',
        'deal_id',
        'creator_id',
        'document_type_id',
        'attachment_id',
        'bill_to_id',
        'bill_from_id',
    ];

    protected $casts = [
        'created_at_api' => 'timestamp',
        'issued_on' => 'date',
        'delivery_on' => 'date',
        'sent_on' => 'date',
        'received_on' => 'date',
        'exchange_rate' => 'decimal:4',
        'exchange_date' => 'date',
        'total_cost' => 'decimal:4',
        'total_cost_default' => 'decimal:4',
        'total_cost_normalized' => 'decimal:4',
        'total_cost_with_tax' => 'decimal:4',
        'total_cost_with_tax_default' => 'decimal:4',
        'total_cost_with_tax_normalized' => 'decimal:4',
        'total_received' => 'decimal:4',
        'total_received_default' => 'decimal:4',
        'total_received_normalized' => 'decimal:4',
    ];

    /**
     * Get the deal associated with the purchase order.
     */
    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    /**
     * Get the creator of the purchase order.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the document type associated with the purchase order.
     */
    public function documentType()
    {
        return $this->belongsTo(ProductiveDocumentType::class, 'document_type_id');
    }

    /**
     * Get the attachment associated with the purchase order.
     */
    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }

    /**
     * Get the bill to contact associated with the purchase order.
     */
    public function billTo()
    {
        return $this->belongsTo(ProductiveContactEntry::class, 'bill_to_id');
    }

    /**
     * Get the bill from contact associated with the purchase order.
     */
    public function billFrom()
    {
        return $this->belongsTo(ProductiveContactEntry::class, 'bill_from_id');
    }
}
