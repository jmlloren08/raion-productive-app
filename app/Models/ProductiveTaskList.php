<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveTaskList extends Model
{
    use SoftDeletes;

    protected $table = 'productive_task_lists';

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
        'email_key',
        // Relationships
        'project_id',
        'board_id',
    ];

    protected $casts = [
        'archived_at' => 'timestamp',
    ];

    /**
     * Get the project associated with the task list.
     */
    public function project()
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }

    /**
     * Get the board associated with the task list.
     */
    public function board()
    {
        return $this->belongsTo(ProductiveBoard::class, 'board_id');
    }
}
