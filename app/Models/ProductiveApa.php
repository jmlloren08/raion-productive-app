<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveApa extends Model
{
    use SoftDeletes;
    
    protected $table = 'productive_apas';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'type',
        'target_type',
        
        'person_id',
        'deal_id',
        'approval_policy_id',
    ];

    /**
     * Get the person associated with the approval policy assignment.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'person_id');
    }

    /**
     * Get the deal associated with the approval policy assignment.
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    /**
     * Get the approval policy associated with the assignment.
     */
    public function approvalPolicy(): BelongsTo
    {
        return $this->belongsTo(ProductiveApprovalPolicy::class, 'approval_policy_id');
    }
}
