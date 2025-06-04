<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveCustomDomain extends Model
{
    use SoftDeletes;

    protected $table = 'productive_custom_domains';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'verified_at',
        'email_sender_name',
        'email_sender_address',
        'mailgun_dkim',
        'mailgun_spf',
        'mailun_mx',
        'allow_user_email',
        // Relationships
        'subsidiary_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'verified_at' => 'timestamp',
        'allow_user_email' => 'boolean',
        'mailgun_mx' => 'array',
    ];

    public function subsidiary()
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'subsidiary_id');
    }
}
