<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'productive_templates';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
