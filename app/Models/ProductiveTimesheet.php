<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveTimesheet extends Model
{
    use SoftDeletes;

    protected $table = 'productive_timesheets';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'date',
        'created_at_api',

        'person_id',
        'creator_id',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at_api' => 'timestamp',
    ];

    /**
     * Get the person associated with the timesheet.
     */
    public function person()
    {
        return $this->belongsTo(ProductivePeople::class, 'person_id');
    }

    /**
     * Get the creator associated with the timesheet.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }
}
