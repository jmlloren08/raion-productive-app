<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductivePipeline extends Model
{
    use SoftDeletes;

    protected $table = 'productive_pipelines';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Disable Laravel timestamps

    protected $fillable = [
        'id',
        'type',
        'name',
    ];

    public function dealStatuses(): HasMany
    {
        return $this->hasMany(ProductiveDealStatus::class, 'pipeline_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(ProductiveDeal::class, 'pipeline_id');
    }
}
