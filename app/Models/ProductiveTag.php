<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveTag extends Model
{
    protected $table = 'productive_tags';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [

        'id',
        'type',
        
        'name',
        'color',
    ];

}
