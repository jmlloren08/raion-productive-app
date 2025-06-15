<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveTeam extends Model
{
    protected $table = 'productive_teams';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'color_id',
        'icon_id',
        'name',

        'members_included',
    ];
}
