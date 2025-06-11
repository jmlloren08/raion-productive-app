<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveCustomField extends Model
{
    protected $table = 'productive_custom_fields';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'created_at_api',
        'updated_at_api',
        'name',
        'data_type',
        'required',
        'description',
        'archived_at',
        'aggregation_type_id',
        'formatting_type_id',
        'global',
        'show_in_add_edit_views',
        'sensitive',
        'position',
        'quick_add_enabled',
        'customizable_type',
        // Relationships
        'project_id',
        'section_id',
        'survey_id',
        'person_id',
        'cfo_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */

    protected $casts = [
        'created_at_api' => 'timestamp',
        'updated_at_api' => 'timestamp',
        'required' => 'boolean',
        'archived_at' => 'timestamp',
        'global' => 'boolean',
        'show_in_add_edit_views' => 'boolean',
        'sensitive' => 'boolean',
        'quick_add_enabled' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }

    public function section()
    {
        return $this->belongsTo(ProductiveSection::class, 'section_id');
    }

    public function survey()
    {
        return $this->belongsTo(ProductiveSurvey::class, 'survey_id');
    }

    public function person()
    {
        return $this->belongsTo(ProductivePeople::class, 'person_id');
    }

    public function cfo()
    {
        return $this->belongsTo(ProductiveCfo::class, 'cfo_id');
    }

}
