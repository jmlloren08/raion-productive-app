<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductiveCompany extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'productive_created_at',
        'productive_updated_at',
    ];

    protected $casts = [
        'productive_created_at' => 'datetime',
        'productive_updated_at' => 'datetime',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(ProductiveProject::class, 'company_id');
    }
}
