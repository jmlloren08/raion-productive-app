<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveProject extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'company_id',
        'name',
        'status',
        'productive_created_at',
        'productive_updated_at',
    ];

    protected $casts = [
        'productive_created_at' => 'datetime',
        'productive_updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ProductiveCompany::class, 'company_id');
    }
}
