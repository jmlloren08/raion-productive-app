<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveTimeEntry extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'type',
        'date',
        'created_at_api',
        'time',
        'billable_time',
        'note',
        'track_method_id',
        'started_at',
        'timer_started_at',
        'timer_stopped_at',
        'approved',
        'approved_at',
        'updated_at_api',
        'calendar_event_id',
        'invoice_attribution_id',
        'invoiced',
        'overhead',
        'rejected',
        'rejected_reason',
        'rejected_at',
        'last_activity_at',
        'submitted',
        'currency',
        'currency_default',
        'currency_normalized',
        'organization_id',
        'person_id',
        'service_id',
        'task_id',
        'deal_id',
        'approver_id',
        'updater_id',
        'rejecter_id',
        'creator_id',
        'last_actor_id',
        'person_subsidiary_id',
        'deal_subsidiary_id',
        'timesheet_id'
    ];

    protected $casts = [
        'date' => 'date',
        'started_at' => 'datetime',
        'timer_started_at' => 'datetime',
        'timer_stopped_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'created_at_api' => 'datetime',
        'updated_at_api' => 'datetime',
        'approved' => 'boolean',
        'invoiced' => 'boolean',
        'overhead' => 'boolean',
        'rejected' => 'boolean',
        'submitted' => 'boolean'
    ];

    /**
     * Get the organization that owns the time entry.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(ProductiveOrganization::class, 'organization_id');
    }

    /**
     * Get the deal associated with the time entry.
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    /**
     * Get the project associated with the time entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }

    /**
     * Get the approver of the time entry.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the person who created the time entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
