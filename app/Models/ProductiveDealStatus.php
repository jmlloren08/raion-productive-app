<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveDealStatus extends Model
{
    use SoftDeletes;

    protected $table = 'productive_deal_statuses';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'type',
        'name',
        'position',
        'color_id',
        'archived_at',
        'time_tracking_enabled',
        'expense_tracking_enabled',
        'booking_tracking_enabled',
        'status_id',
        'probability_enabled',
        'probability',
        'lost_reason_enabled',
        'used',
        
        'pipeline_id'
    ];

    protected $casts = [
        'time_tracking_enabled' => 'boolean',
        'expense_tracking_enabled' => 'boolean',
        'booking_tracking_enabled' => 'boolean',
        'probability_enabled' => 'boolean',
        'lost_reason_enabled' => 'boolean',
        'used' => 'boolean',
        'archived_at' => 'datetime',
        'probability' => 'decimal:2',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(ProductivePipeline::class, 'pipeline_id');
    }

}
