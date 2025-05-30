<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveComment extends Model
{
    use SoftDeletes;

    protected $table = 'productive_comments';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'body',
        'commentable_type',
        'created_at_api',
        'deleted_at_api',
        'draft',
        'edited_at',
        'hidden',
        'pinned_at',
        'reactions',
        'updated_at_api',
        'version_number',
        // Relationships
        'company_id',
        'creator_id',
        'deal_id',
        'discussion_id',
        'invoice_id',
        'person_id',
        'pinned_by_id',
        'task_id',
        'purchase_order_id',
        'attachment_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at_api' => 'timestamp',
        'deleted_at_api' => 'timestamp',
        'edited_at' => 'timestamp',
        'pinned_at' => 'timestamp',
        'reactions' => 'array',
        'updated_at_api' => 'timestamp',
        'draft' => 'boolean',
        'hidden' => 'boolean',
        'version_number' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(ProductiveCompany::class, 'company_id');
    }

    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }

    public function discussion()
    {
        return $this->belongsTo(ProductiveDiscussion::class, 'discussion_id');
    }

    public function invoice()
    {
        return $this->belongsTo(ProductiveInvoice::class, 'invoice_id');
    }

    public function person()
    {
        return $this->belongsTo(ProductivePeople::class, 'person_id');
    }

    public function pinnedBy()
    {
        return $this->belongsTo(ProductivePeople::class, 'pinned_by_id');
    }

    public function task()
    {
        return $this->belongsTo(ProductiveTask::class, 'task_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ProductivePurchaseOrder::class, 'purchase_order_id');
    }

    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }
}
