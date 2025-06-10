<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveCfo extends Model
{
    use SoftDeletes;

    protected $table = 'productive_cfos';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'archived_at',
        'position',
        'color_id',
        // Relationships
        'custom_field_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */

    protected $casts = [
        'archived_at' => 'timestamp',
        'position' => 'integer',
        'color_id' => 'string',
    ];

    /**
     * Get the custom field that owns the CFO.
     */

    public function customField()
    {
        return $this->belongsTo(ProductiveCustomField::class, 'custom_field_id');
    }
}
