<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveService extends Model
{
    protected $table = 'productive_services';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'position',
        'deleted_at_api',
        'billable',
        'description',
        'time_tracking_enabled',
        'expense_tracking_enabled',
        'booking_tracking_enabled',
        'origin_service_id',
        'initial_service_id',
        'budget_cap_enabled',
        'editor_config',
        'custom_fields',
        'pricing_type_id',
        'billing_type_id',
        'unapproved_time',
        'worked_time',
        'billable_time',
        'estimated_time',
        'budgeted_time',
        'rolled_over_time',
        'booked_time',
        'unit_id',
        'future_booked_time',
        'markup',
        'discount',
        'quantity',
        'currency',
        'currency_default',
        'currency_normalized',
        'price',
        'price_default',
        'price_normalized',
        'revenue',
        'revenue_default',
        'revenue_normalized',
        'projected_revenue',
        'projected_revenue_default',
        'projected_revenue_normalized',
        'expense_amount',
        'expense_amount_default',
        'expense_amount_normalized',
        'expense_billable_amount',
        'expense_billable_amount_default',
        'expense_billable_amount_normalized',
        'budget_total',
        'budget_total_default',
        'budget_total_normalized',
        'budget_used',
        'budget_used_default',
        'budget_used_normalized',
        'future_revenue',
        'future_revenue_default',
        'future_revenue_normalized',
        'future_budget_used',
        'future_budget_used_default',
        'future_budget_used_normalized',
        'discount_amount',
        'discount_amount_default',
        'discount_amount_normalized',
        'markup_amount',
        'markup_amount_default',
        'markup_amount_normalized',
        // Relationships
        'service_type_id',
        'deal_id',
        'person_id',
        'section_id',

        'custom_field_people',
        'custom_field_attachments',
    ];

    protected $casts = [
        'deleted_at_api' => 'timestamp',
        'billable' => 'boolean',
        'time_tracking_enabled' => 'boolean',
        'expense_tracking_enabled' => 'boolean',
        'booking_tracking_enabled' => 'boolean',
        'budget_cap_enabled' => 'boolean',
        'editor_config' => 'array',
        'custom_fields' => 'array',
        'unapproved_time' => 'integer',
        'worked_time' => 'integer',
        'billable_time' => 'integer',
        'estimated_time' => 'integer',
        'budgeted_time' => 'integer',
        'rolled_over_time' => 'integer',
        'booked_time' => 'integer',
        'future_booked_time' => 'integer',
        'markup' => 'decimal:2',
        'discount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'currency' => 'string',
        'currency_default' => 'string',
        'currency_normalized' => 'string',
        'price' => 'decimal:2',
        'price_default' => 'decimal:2',
        'price_normalized' => 'decimal:2',
        'revenue' => 'decimal:2',
        'revenue_default' => 'decimal:2',
        'revenue_normalized' => 'decimal:2',
    ];

    /**
     * Get the service type associated with the service.
     */
    public function serviceType()
    {
        return $this->belongsTo(ProductiveServiceType::class, 'service_type_id');
    }

    /**
     * Get the deal associated with the service.
     */
    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    /**
     * Get the person associated with the service.
     */
    public function person()
    {
        return $this->belongsTo(ProductivePeople::class, 'person_id');
    }

    /**
     * Get the section associated with the service.
     */
    public function section()
    {
        return $this->belongsTo(ProductiveSection::class, 'section_id');
    }
}
