<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveDeal extends Model
{
    use SoftDeletes;

    protected $table = 'productive_deals';

    public $incrementing = false;
    public $timestamps = false; // Disable Laravel timestamps

    protected $fillable = [
        'id',
        'type',
        'name',
        'date',
        'end_date',
        'number',
        'deal_number',
        'suffix',
        'time_approval',
        'expense_approval',
        'client_access',
        'deal_type_id',
        'budget',
        'sales_status_updated_at',
        'tag_list',
        'origin_deal_id',
        'email_key',
        'purchase_order_number',
        'custom_fields',
        'position',
        'service_type_restricted_tracking',
        'tracking_type_id',
        'editor_config',
        'discount',
        'man_day_minutes',
        'rounding_interval_id',
        'rounding_method_id',
        'validate_expense_when_closing',
        'billable_time',
        'budget_warning',
        'estimated_time',
        'budgeted_time',
        'worked_time',
        'time_to_close',
        'probability',
        'previous_probability',
        'note_interpolated',
        'proposal_note_interpolated',
        'todo_count',
        'todo_due_date',
        'note',
        'proposal_note',
        'lost_comment',
        'closed_at',
        'delivered_on',
        'last_activity_at',
        'deleted_at',
        'created_at_api',
        'sales_closed_at',
        'exchange_rate',
        'exchange_date',
        'sales_closed_on',
        'sample_data',
        'external_id',
        'external_sync',
        'manual_invoicing_status_id',
        'currency',
        'currency_default',
        'currency_normalized',
        'revenue',
        'revenue_default',
        'revenue_normalized',
        'services_revenue',
        'services_revenue_default',
        'services_revenue_normalized',
        'budget_total',
        'budget_total_default',
        'budget_total_normalized',
        'budget_used',
        'budget_used_default',
        'budget_used_normalized',
        'projected_revenue',
        'projected_revenue_default',
        'projected_revenue_normalized',
        'invoiced',
        'invoiced_default',
        'invoiced_normalized',
        'pending_invoicing',
        'pending_invoicing_default',
        'pending_invoicing_normalized',
        'manually_invoiced',
        'manually_invoiced_default',
        'manually_invoiced_normalized',
        'draft_invoiced',
        'draft_invoiced_default',
        'draft_invoiced_normalized',
        'amount_credited',
        'amount_credited_default',
        'amount_credited_normalized',
        'expense',
        'expense_default',
        'expense_normalized',
        'creator_id',
        'company_id',
        'document_type_id',
        'proposal_document_type',
        'responsible_id',
        'deal_status_id',
        'project_id',
        'lost_reason_id',
        'contract_id',
        'contact_id',
        'subsidiary_id',
        'template',
        'tax_rate_id',
        'pipeline',
        'origin_deal',
        'apa_id',
        'next_todo',
        'custom_field_people',
        'custom_field_attachments',
    ];

    protected $casts = [
        'tag_list' => 'array',
        'custom_fields' => 'array',
        'editor_config' => 'array',
        'date' => 'date',
        'end_date' => 'date',
        'todo_due_date' => 'date',
        'closed_at' => 'timestamp',
        'delivered_on' => 'date',
        'last_activity_at' => 'timestamp',
        'created_at_api' => 'timestamp',
        'sales_closed_at' => 'datetime',
        'sales_closed_on' => 'date',
        'exchange_date' => 'date',
        'time_approval' => 'boolean',
        'expense_approval' => 'boolean',
        'client_access' => 'boolean',
        'budget' => 'boolean',
        'service_type_restricted_tracking' => 'boolean',
        'validate_expense_when_closing' => 'boolean',
        'sample_data' => 'boolean',
        'external_sync' => 'boolean',
    ];

    /**
     * Get the creator associated with the deal.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the company associated with the deal.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(ProductiveCompany::class, 'company_id');
    }

    /**
     * Get the dcoument type associated with the deal.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(ProductiveDocumentType::class, 'document_type_id');
    }

    /**
     * Get the responsible associated with the deal.
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'responsible_id');
    }

    /**
     * Get the deal status associated with the deal.
     */
    public function dealStatus(): BelongsTo
    {
        return $this->belongsTo(ProductiveDealStatus::class, 'deal_status_id');
    }

    /**
     * Get the project associated with the deal.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }

    /**
     * Get the lost reason associated with the deal.
     */
    public function lostReason(): BelongsTo
    {
        return $this->belongsTo(ProductiveLostReason::class, 'lost_reason_id');
    }

    /**
     * Get the contract associated with the deal.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProductiveContract::class, 'contract_id');
    }

    /**
     * Get the contact associated with the deal.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(ProductiveContactEntry::class, 'contact_id');
    }

    /**
     * Get the subsidiary associated with the deal.
     */
    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'subsidiary_id');
    }

    /**
     * Get the tax rate associated with the deal.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(ProductiveTaxRate::class, 'tax_rate_id');
    }

    /**
     * Get the apa associated with the deal.
     */
    public function apa(): BelongsTo
    {
        return $this->belongsTo(ProductiveApa::class, 'apa_id');
    }
}
