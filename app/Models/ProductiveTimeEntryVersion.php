<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveTimeEntryVersion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'event',
        'object_changes',
        'item_id',
        'item_type',
        'created_at_api',
        'organization_id',
        'creator_id'
    ];

    protected $casts = [
        'object_changes' => 'json',
        'created_at_api' => 'datetime'
    ];

    /**
     * Get the organization that owns the time entry version.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(ProductiveOrganization::class, 'organization_id');
    }

    /**
     * Get the creator of this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the time entry that this version belongs to.
     */
    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(ProductiveTimeEntry::class, 'item_id')->where('item_type', 'TimeEntry');
    }
}
