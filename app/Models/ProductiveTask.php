<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveTask extends Model
{
    use SoftDeletes;

    protected $table = 'productive_tasks';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'title',
        'description',
        'number',
        'task_number',
        'private',
        'due_date',
        'start_date',
        'closed_at',
        'created_at_api',
        'updated_at_api',
        'repeat_schedule_id',
        'repeat_on_interval',
        'repeat_on_monthday',
        'repeat_on_weekday',
        'repeat_on_date',
        'repeat_origin_id',
        'email_key',
        'custom_fields',
        'todo_count',
        'open_todo_count',
        'subtask_count',
        'open_subtask_count',
        'creation_method_id',
        'todo_assignee_ids',
        'task_dependency_count',
        'type_id',
        'blocking_dependency_count',
        'waiting_on_dependency_count',
        'linked_dependency_count',
        'placement',
        'subtask_placement',
        'closed',
        'due_time',
        'tag_list',
        'last_activity_at',
        'initial_estimate',
        'remaining_time',
        'billable_time',
        'worked_time',
        'deleted_at_api',
        // Relationships
        'project_id',
        'creator_id',
        'assignee_id',
        'last_actor_id',
        'task_list_id',
        'parent_task_id',
        'workflow_status_id',

        'repeated_task',

        'attachment_id',
        // Arrays
        'custom_field_people',
        'custom_field_attachments',
    ];

    protected $casts = [
        'private' => 'boolean',
        'due_date' => 'date',
        'start_date' => 'date',
        'closed_at' => 'timestamp',
        'created_at_api' => 'timestamp',
        'updated_at_api' => 'timestamp',
        'repeat_on_weekday' => 'array',
        'repeat_on_date' => 'date',
        'custom_fields' => 'array',
        'todo_assignee_ids' => 'array',
        'tag_list' => 'array',
        'repeated_task' => 'array',
        'deleted_at_api' => 'timestamp',
        'custom_field_people' => 'array',
        'custom_field_attachments' => 'array',
    ];

    /**
     * Get the project associated with the task.
     */
    public function project()
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }

    /**
     * Get the creator of the task.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the assignee of the task.
     */
    public function assignee()
    {
        return $this->belongsTo(ProductivePeople::class, 'assignee_id');
    }

    /**
     * Get the last actor of the task.
     */
    public function lastActor()
    {
        return $this->belongsTo(ProductivePeople::class, 'last_actor_id');
    }

    /**
     * Get the task list associated with the task.
     */
    public function taskList()
    {
        return $this->belongsTo(ProductiveTaskList::class, 'task_list_id');
    }

    /**
     * Get the parent task associated with the task.
     */
    public function parentTask()
    {
        return $this->belongsTo(ProductiveTask::class, 'parent_task_id');
    }

    /**
     * Get the workflow status associated with the task.
     */
    public function workflowStatus()
    {
        return $this->belongsTo(ProductiveWorkflowStatus::class, 'workflow_status_id');
    }

    /**
     * Get the attachment associated with the task.
     */
    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }

}
