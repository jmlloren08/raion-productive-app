<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveWorkflowStatus extends Model
{
    use SoftDeletes;

    protected $table = 'productive_workflow_statuses';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'color_id',
        'position',
        'category_id',
        // Relationships
        'workflow_id',
    ];

    public function workflow()
    {
        return $this->belongsTo(ProductiveWorkflow::class, 'workflow_id');
    }
}
