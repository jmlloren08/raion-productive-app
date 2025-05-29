<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveWorkflow extends Model
{
    use SoftDeletes;

    protected $table = 'productive_workflows';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'type',
        'name',
        'archived_at',

        'workflow_status_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'archived_at' => 'timestamp',
    ];

    /**
     * Get the workflow status.
     */
    public function workflowStatus()
    {
        return $this->belongsTo(ProductiveWorkflowStatus::class, 'workflow_status_id');
    }
    
}
