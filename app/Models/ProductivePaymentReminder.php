<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductivePaymentReminder extends Model
{
    protected $table = 'productive_payment_reminders';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [

        'id',
        'type',

        'created_at_api',
        'updated_at_api',
        'subject',
        'subject_parsed',
        'to',
        'from',
        'cc',
        'bcc',
        'body',
        'body_parsed',
        'scheduled_on',
        'sent_at',
        'delivered_at',
        'failed_at',
        'stopped_at',
        'before_due_date',
        'reminder_period',
        'reminder_stopped_reason_id',

        'creator_id',
        'updater_id',
        'invoice_id',
        'prs_id'
    ];

    protected $casts = [
        'created_at_api' => 'timestamp',
        'updated_at_api' => 'timestamp',
        'to' => 'array',
        'from' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'body' => 'string',
        'body_parsed' => 'string',
        'scheduled_on' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'stopped_at' => 'datetime',
        'before_due_date' => 'boolean',
        'reminder_period' => 'integer',
        'reminder_stopped_reason_id' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    public function updater()
    {
        return $this->belongsTo(ProductivePeople::class, 'updater_id');
    }

    public function invoice()
    {
        return $this->belongsTo(ProductiveInvoice::class, 'invoice_id');
    }

    public function prs()
    {
        return $this->belongsTo(ProductivePrs::class, 'prs_id');
    }
}
