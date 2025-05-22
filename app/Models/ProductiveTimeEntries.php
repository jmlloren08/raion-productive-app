<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveTimeEntries extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
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
        'organization_id',
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
        'timesheet_id',
        'productive_id'
    ];

    protected $casts = [
        'date' => 'date',
        'created_at_api' => 'timestamp',
        'started_at' => 'datetime',
        'timer_started_at' => 'datetime',
        'timer_stopped_at' => 'datetime',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
        'updated_at_api' => 'timestamp',
        'invoiced' => 'boolean',
        'overhead' => 'boolean',
        'rejected' => 'boolean',
        'rejected_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'submitted' => 'boolean',
    ];

    /**
     * Get the organization that owns the time entry.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    /*
    public function organization(): BelongsTo
    {
        return $this->belongsTo(ProductiveOrganization::class, 'organization_id');
    }
    */

    /**
     * Get the person that owns the time entry.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    /*
    public function person(): BelongsTo
    {
        return $this->belongsTo(ProductivePerson::class, 'person_id');
    }
    */

    /**
     * Get the service associated with the time entry.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    /*
    public function service(): BelongsTo
    {
        return $this->belongsTo(ProductiveService::class, 'service_id');
    }
    */

    /**
     * Get the task associated with the time entry.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    /*
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductiveTask::class, 'task_id');
    }
    */

    public function deal(): BelongsTo
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    public function timeVersions()
    {
        return $this->hasMany(ProductiveTimeEntryVersions::class, 'item_id');
    }

    // public function task(): BelongsTo
    // {
    //     return $this->belongsTo(ProductiveTask::class, 'task_id');
    // }

    // public function service(): BelongsTo
    // {
    //     return $this->belongsTo(ProductiveService::class, 'service_id');
    // }
}
