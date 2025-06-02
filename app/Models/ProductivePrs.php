<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductivePrs extends Model
{
    use SoftDeletes;

    protected $table = 'productive_prs';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
