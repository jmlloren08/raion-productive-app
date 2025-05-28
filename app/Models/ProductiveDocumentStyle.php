<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveDocumentStyle extends Model
{
    use SoftDeletes;

    protected $table = 'productive_document_styles';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'type',
        'name',
        'styles',
        'attachment_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'styles' => 'array',
    ];

    /**
     * Get the attachment associated with this document style.
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }
}
