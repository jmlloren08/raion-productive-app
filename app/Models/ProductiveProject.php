<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveProject extends Model
{
    protected $table = 'productive_projects';
    
    public $incrementing = false;
    public $timestamps = false; // Disable Laravel timestamps

    protected $fillable = [
        'id',
        'type',
        'name',
        'number',
        'project_number',
        'project_type_id',
        'project_color_id',
        'last_activity_at',
        'public_access',
        'time_on_tasks',
        'archived_at',
        'created_at_api',
        'template',
        'custom_fields',
        'task_custom_fields_ids',
        'task_custom_fields_positions',
        'sample_data',
        'preferences',
        'tag_colors',
        'company_id',
        'project_manager_id',
        'last_actor_id',
        'workflow_id',
    ];
    protected $casts = [
        'custom_fields' => 'array',
        'task_custom_fields_ids' => 'array',
        'task_custom_fields_positions' => 'array',
        'preferences' => 'array',
        'tag_colors' => 'array',
        'last_activity_at' => 'datetime',
        'archived_at' => 'datetime',
        'created_at_api' => 'datetime',
        'public_access' => 'boolean',
        'time_on_tasks' => 'boolean',
        'template' => 'boolean',
        'sample_data' => 'boolean',
    ];

    /**
     * Get the company associated with the project.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(ProductiveCompany::class, 'company_id');
    }

    /**
     * Get the project manager associated with the project.
     */
    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'project_manager_id');
    }

    /**
     * Get the last actor associated with the project.
     */
    public function lastActor(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'last_actor_id');
    }

    /**
     * Get the workflow associated with the project.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ProductiveWorkflow::class, 'workflow_id');
    }

}
