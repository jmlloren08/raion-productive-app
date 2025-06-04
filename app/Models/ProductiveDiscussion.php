<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveDiscussion extends Model
{
    use SoftDeletes;

    protected $table = 'productive_discussions';
    
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'excerpt',
        'resolved_at',
        'subscriber_ids',
        // Relationships
        'page_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'resolved_at' => 'timestamp',
        'subscriber_ids' => 'array',
    ];
    
    public function page()
    {
        return $this->belongsTo(ProductivePage::class, 'page_id');
    }
}
