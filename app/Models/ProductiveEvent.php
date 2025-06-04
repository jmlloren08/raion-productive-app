<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveEvent extends Model
{
    use SoftDeletes;

    protected $table = 'productive_events';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'event_type_id',
        'icon_id',
        'color_id',
        'archived_at',
        'limitation_type_id',
        'sync_personal_integrations',
        'half_day_bookings',
        'description',
        'absence_type',
    ];

    protected $casts = [
        'event_type_id' => 'integer',
        'sync_personal_integrations' => 'boolean',
        'half_day_bookings' => 'boolean',
        'archived_at' => 'timestamp',
        'limitation_type_id' => 'integer',
    ];
    
}
