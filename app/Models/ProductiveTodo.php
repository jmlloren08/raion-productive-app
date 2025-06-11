<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveTodo extends Model
{
    protected $table = 'productive_todos';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'description',
        'closed_at',
        'closed',
        'due_date',
        'created_at_api',
        'todoable_type',
        'due_time',
        'position',
        // Relationships
        'assignee_id',
        'deal_id',
        'task_id',
    ];

    protected $casts = [
        'closed_at' => 'timestamp',
        'due_date' => 'date',
        'created_at_api' => 'timestamp',
        'position' => 'integer',
    ];

    public function assignee()
    {
        return $this->belongsTo(ProductivePeople::class, 'assignee_id');
    }

    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    public function task()
    {
        return $this->belongsTo(ProductiveTask::class, 'task_id');
    }
}
