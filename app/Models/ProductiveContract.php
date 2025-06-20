<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveContract extends Model
{
    protected $table = 'productive_contracts';
    
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
        
        'ends_on',
        'starts_on',
        'next_occurrence_on',
        'interval_id',
        'copy_purchase_order_number',
        'copy_expenses',
        'use_rollover_hours',

        'deal_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'ends_on' => 'date',
        'starts_on' => 'date',
        'next_occurrence_on' => 'date',
        'copy_purchase_order_number' => 'boolean',
        'copy_expenses' => 'boolean',
        'use_rollover_hours' => 'boolean',
    ];

    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

}
