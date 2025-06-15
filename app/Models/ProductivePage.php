<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductivePage extends Model
{
    protected $table = 'productive_pages';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'cover_image_meta',
        'cover_image_url',
        'created_at_api',
        'edited_at_api',
        'icon_id',
        'position',
        'preferences',
        'title',
        'updated_at_api',
        'version_number',
        'last_activity_at',
        'body',
        'parent_page_id',
        'root_page_id',
        'public_uuid',
        'public',
        // Relationships
        'creator_id',
        'project_id',
        'attachment_id',

        'template_object',
    ];

    protected $casts = [
        'cover_image_meta' => 'string',
        'cover_image_url' => 'string',
        'created_at_api' => 'timestamp',
        'edited_at_api' => 'timestamp',
        'icon_id' => 'integer',
        'position' => 'integer',
        'preferences' => 'array',
        'title' => 'string',
        'updated_at_api' => 'timestamp',
        'version_number' => 'integer',
        'last_activity_at' => 'timestamp',
        'body' => 'array',
        'parent_page_id' => 'integer',
        'root_page_id' => 'integer',
        'public_uuid' => 'string',
        'public' => 'boolean',
    ];

    /**
     * Get the creator of the page.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the project associated with the page.
     */
    public function project()
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }

    /**
     * Get the attachment associated with the page.
     */
    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }
}
