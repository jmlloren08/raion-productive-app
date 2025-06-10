<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveCustomFieldValue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'custom_field_id',
        'custom_field_option_id',
        'custom_field_name',
        'custom_field_value',
        'raw_value'
    ];

    /**
     * Get the project that owns the custom field value.
     */
    public function project()
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }

    /**
     * Get the custom field that owns the value.
     */
    public function customField()
    {
        return $this->belongsTo(ProductiveCustomField::class, 'custom_field_id');
    }

    /**
     * Get the custom field option that owns the value.
     */
    public function customFieldOption()
    {
        return $this->belongsTo(ProductiveCfo::class, 'custom_field_option_id');
    }
}
