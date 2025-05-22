<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveTimeEntryVersions extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'event',
        'object_changes',
        'item_id',
        'item_type',
        'created_at_api',
        'organization_id',
        'creator_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'object_changes' => 'json',
        'created_at_api' => 'timestamp',
    ];

    /**
     * Get the time entry that this version belongs to.
     */
    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(ProductiveTimeEntries::class, 'item_id');
    }

    // /**
    //  * Get the organization that this version belongs to.
    //  */
    // public function organization(): BelongsTo
    // {
    //     return $this->belongsTo(ProductiveOrganization::class, 'organization_id', 'id');
    // }

    // /**
    //  * Get the creator of this version.
    //  */
    // public function creator(): BelongsTo
    // {
    //     return $this->belongsTo(ProductivePerson::class, 'creator_id', 'id');
    // }
}
