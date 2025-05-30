<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveApprovalPolicy extends Model
{
    use SoftDeletes;

    protected $table = 'productive_approval_policies';

    protected $keyType = 'string';
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
