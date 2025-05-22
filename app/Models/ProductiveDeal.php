<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveDeal extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
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
        'company_id',
        'project_id',
        'organization_id',
        'productive_id'
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(ProductiveCompany::class, 'company_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }
}
