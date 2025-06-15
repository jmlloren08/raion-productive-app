<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveApprovalPolicy extends Model
{
    protected $table = 'productive_approval_policies';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'type',
        'archived_at',
        'custom',
        'default',
        'description',
        'name',
        'type_id',
    ];
    
}
