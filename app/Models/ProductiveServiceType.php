<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveServiceType extends Model
{
    use SoftDeletes;

    protected $table = 'productive_service_types';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'archived_at',
        'description',
        // Relationships
        'assignee_id',
    ];

    protected $casts = [
        'archived_at' => 'timestamp',
        'description' => 'text',
    ];

    /**
     * Get the assignee associated with the service type.
     */
    public function assignee()
    {
        return $this->belongsTo(ProductivePeople::class, 'assignee_id');
    }
}
