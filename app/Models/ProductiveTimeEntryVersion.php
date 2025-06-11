<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveTimeEntryVersion extends Model
{
    protected $table = 'productive_time_entry_versions';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'type',
        'event',
        'object_changes',
        'item_id',
        'item_type',
        'created_at_api',
        'creator_id'
    ];

    protected $casts = [
        'object_changes' => 'array',
        'created_at_api' => 'timestamp',
    ];

    /**
     * Get the creator of this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

}
