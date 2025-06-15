<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveLostReason extends Model
{
    protected $table = 'productive_lost_reasons';

    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        'id',
        'type',

        'name',
        'archived_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'archived_at' => 'timestamp',
    ];

}
