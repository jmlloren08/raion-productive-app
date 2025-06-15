<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveCustomFieldValue extends Model
{

    protected $fillable = [
        'entity_id',
        'entity_type',
        'custom_field_id',
        'custom_field_option_id',
        'custom_field_name',
        'custom_field_value',
        'raw_value'
    ];

    /**
     * Get the owning entity model.
     */
    public function entity()
    {
        return $this->morphTo();
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

    public function project()
    {
        return $this->belongsTo(ProductiveProject::class, 'entity_id');
    }
    
    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'entity_id');
    }
}
