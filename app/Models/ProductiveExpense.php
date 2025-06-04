<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveExpense extends Model
{
    use SoftDeletes;

    protected $table = 'productive_expenses';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'date',
        'pay_on',
        'paid_on',
        'position',
        'invoiced',
        'approved',
        'approved_at',
        'rejected',
        'rejected_reason',
        'rejected_at',
        'deleted_at_api',
        'reimbursable',
        'reimbursed_on',
        'exchange_rate',
        'exchange_rate_normalized',
        'exchange_date',
        'created_at_api',
        'quantity',
        'quantity_received',
        'custom_fields',
        'draft',
        'exported',
        'exported_at',
        'export_integration_type_id',
        'export_id',
        'export_url',
        'company_reference_id',
        'external_payment_id',
        'currency',
        'currency_default',
        'currency_normalized',
        'amount',
        'amount_default',
        'amount_normalized',
        'total_amount',
        'total_amount_default',
        'total_amount_normalized',
        'billable_amount',
        'billable_amount_default',
        'billable_amount_normalized',
        'profit',
        'profit_default',
        'profit_normalized',
        'recognized_revenue',
        'recognized_revenue_default',
        'recognized_revenue_normalized',
        'amount_with_tax',
        'amount_with_tax_default',
        'amount_with_tax_normalized',
        'total_amount_with_tax',
        'total_amount_with_tax_default',
        'total_amount_with_tax_normalized',
        // Relationships
        'deal_id',
        'service_type_id',
        'person_id',
        'creator_id',
        'approver_id',
        'rejecter_id',
        'service_id',
        'purchase_order_id',
        'tax_rate_id',
        'attachment_id',

        'custom_field_people',
        'custom_field_attachments',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'custom_field_people' => 'array',
        'custom_field_attachments' => 'array',
    ];

    /**
     * Get the deal associated with the expense.
     */
    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    /**
     * Get the service type associated with the expense.
     */
    public function serviceType()
    {
        return $this->belongsTo(ProductiveServiceType::class, 'service_type_id');
    }

    /**
     * Get the person associated with the expense.
     */
    public function person()
    {
        return $this->belongsTo(ProductivePeople::class, 'person_id');
    }

    /**
     * Get the creator of the expense.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the approver of the expense.
     */
    public function approver()
    {
        return $this->belongsTo(ProductivePeople::class, 'approver_id');
    }

    /**
     * Get the rejecter of the expense.
     */
    public function rejecter()
    {
        return $this->belongsTo(ProductivePeople::class, 'rejecter_id');
    }

    /**
     * Get the service associated with the expense.
     */
    public function service()
    {
        return $this->belongsTo(ProductiveService::class, 'service_id');
    }

    /**
     * Get the purchase order associated with the expense.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(ProductivePurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Get the tax rate associated with the expense.
     */
    public function taxRate()
    {
        return $this->belongsTo(ProductiveTaxRate::class, 'tax_rate_id');
    }

    /**
     * Get the attachment associated with the expense.
     */
    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }
}
