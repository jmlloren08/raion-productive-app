<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveSurvey extends Model
{
    protected $table = 'productive_surveys';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'title',
        'description',
        'public_uuid',
        'submission_access',
        'created_at_api',
        'updated_at_api',
        // Relationships
        'project_id',
        'creator_id',
        'updater_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */

    protected $casts = [
        'created_at_api' => 'timestamp',
        'updated_at_api' => 'timestamp',
    ];

    /**
     * Get the project that owns the survey.
     */

    public function project()
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }

    /**
     * Get the creator of the survey.
     */

    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the updater of the survey.
     */

    public function updater()
    {
        return $this->belongsTo(ProductivePeople::class, 'updater_id');
    }
}
