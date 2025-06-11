<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveBoard extends Model
{
    protected $table = 'productive_boards';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'position',
        'placement',
        'archived_at',
        // Relationships
        'project_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'archived_at' => 'timestamp',
    ];

    /**
     * Get the project that owns the board.
     */
    public function project()
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }
}
