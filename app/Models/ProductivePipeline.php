<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductivePipeline extends Model
{
    protected $table = 'productive_pipelines';

    public $incrementing = false;
    public $timestamps = false; // Disable Laravel timestamps

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'created_at_api',
        'updated_at_api',
        'position',
        'icon_id',
        'pipeline_type_id',
        // Relationships
        'creator_id',
        'updater_id',
    ];

    protected $casts = [
        'created_at_api' => 'timestamp',
        'updated_at_api' => 'timestamp',
        'position' => 'integer',
        'icon_id' => 'integer',
        'pipeline_type_id' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'updater_id');
    }
}
