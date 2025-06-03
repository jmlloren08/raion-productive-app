<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveBooking extends Model
{
    use SoftDeletes;

    protected $table = 'productive_bookings';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Core attributes
        'hours',
        'time',
        'started_on',
        'ended_on',
        'note',
        'total_time',
        'total_working_days',
        'percentage',
        'created_at_api',
        'updated_at_api',
        'people_custom_fields',
        'approved',
        'approved_at_api',
        'rejected',
        'rejected_reason',
        'rejected_at_api',
        'canceled',
        'canceled_at_api',
        'booking_method_id',
        'autotracking',
        'draft',
        'custom_fields',
        'external_id',
        'last_activity_at_api',
        'stage_type',
        // Relationships
        'service_id',
        'event_id',
        'person_id',
        'creator_id',
        'updater_id',
        'approver_id',
        'rejecter_id',
        'canceler_id',
        'origin_id',
        'approval_status_id',
        'attachment_id'
    ];
    protected $casts = [
        'started_on' => 'date',
        'ended_on' => 'date',
        'created_at_api' => 'timestamp',
        'updated_at_api' => 'timestamp',
        'approved_at_api' => 'timestamp',
        'rejected_at_api' => 'timestamp',
        'canceled_at_api' => 'timestamp',
        'last_activity_at_api' => 'timestamp',
        'people_custom_fields' => 'array',
        'custom_fields' => 'array',
        'custom_field_people' => 'array',
        'custom_field_attachments' => 'array',
    ];

    /**
     * Get the service associated with the booking.
     */
    public function service()
    {
        return $this->belongsTo(ProductiveService::class, 'service_id');
    }

    /**
     * Get the event associated with the booking.
     */
    public function event()
    {
        return $this->belongsTo(ProductiveEvent::class, 'event_id');
    }

    /**
     * Get the person associated with the booking.
     */
    public function person()
    {
        return $this->belongsTo(ProductivePeople::class, 'person_id');
    }

    /**
     * Get the creator of the booking.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the updater of the booking.
     */
    public function updater()
    {
        return $this->belongsTo(ProductivePeople::class, 'updater_id');
    }

    /**
     * Get the approver of the booking.
     */
    public function approver()
    {
        return $this->belongsTo(ProductivePeople::class, 'approver_id');
    }

    /**
     * Get the rejecter of the booking.
     */
    public function rejecter()
    {
        return $this->belongsTo(ProductivePeople::class, 'rejecter_id');
    }

    /**
     * Get the canceler of the booking.
     */
    public function canceler()
    {
        return $this->belongsTo(ProductivePeople::class, 'canceler_id');
    }

    /**
     * Get the origin associated with the booking.
     */
    public function origin()
    {
        return $this->belongsTo(ProductiveBooking::class, 'origin_id');
    }

    /**
     * Get the approval statuses associated with the booking.
     */
    public function approvalStatuses()
    {
        return $this->hasMany(ProductiveApprovalStatus::class, 'approval_status_id');
    }

    /**
     * Get the attachment associated with the booking.
     */
    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }
}
