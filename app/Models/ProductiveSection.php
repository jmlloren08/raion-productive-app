<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveSection extends Model
{
    protected $table = 'productive_sections';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'preferences',
        'position',
        'editor_config',
        // Relationships
        'deal_id',
    ];

    protected $casts = [
        'preferences' => 'array',
        'editor_config' => 'array',
    ];

    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }
}
