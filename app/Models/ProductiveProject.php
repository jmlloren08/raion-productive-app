<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductiveProject extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Disable Laravel timestamps
      protected $fillable = [
        'id',
        'company_id',
        'type',
        'name',
        'number',
        'preferences',
        'project_number',
        'project_type_id',
        'project_color_id',
        'last_activity_at',
        'public_access',
        'time_on_tasks',
        'tag_colors',
        'archived_at',
        'template',
        'custom_fields',
        'task_custom_fields_ids',
        'task_custom_fields_positions',
        'sample_data',
        'organization_type',
        'organization_id',
        'company_meta_included',
        'project_manager_meta_included',
        'last_actor_meta_included',
        'workflow_meta_included',
        'custom_field_people_meta_included',
        'custom_field_attachments_meta_included',
        'template_object_meta_included',
        'productive_created_at',
        'productive_updated_at',
    ];    protected $casts = [
        'last_activity_at' => 'datetime',
        'archived_at' => 'datetime',
        'preferences' => 'array',
        'public_access' => 'boolean',
        'time_on_tasks' => 'boolean',
        'tag_colors' => 'array',
        'template' => 'boolean',
        'custom_fields' => 'array',
        'task_custom_fields_ids' => 'array',
        'task_custom_fields_positions' => 'array',
        'sample_data' => 'boolean',
        'company_meta' => 'array',
        'project_manager_meta' => 'array',
        'last_actor_meta' => 'array',
        'workflow_meta' => 'array',
        'custom_field_people_meta' => 'array',
        'custom_field_attachments_meta' => 'array',
        'template_object_meta' => 'array',
        'productive_created_at' => 'datetime',
        'productive_updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ProductiveCompany::class, 'company_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(ProductiveDeal::class, 'project_id');
    }
}
