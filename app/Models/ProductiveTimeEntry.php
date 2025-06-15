<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveTimeEntry extends Model
{
    protected $table = 'productive_time_entries';

    public $incrementing = false;
    public $timestamps = false;

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
        'person_id',
        'service_id',
        'task_id',
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
     * Get the person associated with the time entry.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'person_id');
    }

    /**
     * Get the service associated with the time entry.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ProductiveService::class, 'service_id');
    }

    /**
     * Get the task associated with the time entry.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductiveTask::class, 'task_id');
    }

    /**
     * Get the approver associated with the time entry.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'approver_id');
    }

    /**
     * Get the updater associated with the time entry.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'updater_id');
    }

    /**
     * Get the rejecter associated with the time entry.
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'rejecter_id');
    }

    /**
     * Get the creator associated with the time entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the last actor associated with the time entry.
     */
    public function lastActor(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'last_actor_id');
    }

    /**
     * Get the person subsidiary associated with the time entry.
     */
    public function personSubsidiary(): BelongsTo
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'person_subsidiary_id');
    }

    /**
     * Get the deal subsidiary associated with the time entry.
     */
    public function dealSubsidiary(): BelongsTo
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'deal_subsidiary_id');
    }

    /**
     * Get the timesheet associated with the time entry.
     */
    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(ProductiveTimesheet::class, 'timesheet_id');
    }
}
