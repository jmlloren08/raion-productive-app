<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveBill extends Model
{
    use SoftDeletes;

    protected $table = 'productive_bills';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'date',
        'due_date',
        'invoice_number',
        'description',
        'created_at_api',
        'currency',
        'currency_default',
        'currency_normalized',
        'total_received',
        'total_received_default',
        'total_received_normalized',
        'total_cost',
        'total_cost_default',
        'total_cost_normalized',
        // Relationships
        'purchase_order_id',
        'creator_id',
        'deal_id',
        'attachment_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'created_at_api' => 'timestamp',
        'total_received' => 'decimal:2',
        'total_received_default' => 'decimal:2',
        'total_received_normalized' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_cost_default' => 'decimal:2',
        'total_cost_normalized' => 'decimal:2'
    ];

    /**
     * Get the purchase order associated with the bill.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(ProductivePurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Get the creator associated with the bill.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the deal associated with the bill.
     */
    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    /**
     * Get the attachment associated with the bill.
     */
    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }
}
