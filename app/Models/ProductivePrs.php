<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductivePrs extends Model
{
    use SoftDeletes;

    protected $table = 'productive_prs';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [

        'id',
        'type',

        'name',
        'created_at_api',
        'updated_at_api',
        'default_sequence',

        'creator_id',
        'updater_id',
        'payment_reminder_id',
    ];

    protected $casts = [
        'created_at_api' => 'timestamp',
        'updated_at_api' => 'timestamp',
    ];

    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }
    public function updater()
    {
        return $this->belongsTo(ProductivePeople::class, 'updater_id');
    }
    public function paymentReminder()
    {
        return $this->belongsTo(ProductivePaymentReminder::class, 'payment_reminder_id');
    }
}
