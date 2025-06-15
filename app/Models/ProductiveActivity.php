<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveActivity extends Model
{
    protected $table = 'productive_activities';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'type',
        'event',
        'changeset',
        'item_id',
        'item_type',
        'item_name',
        'item_deleted_at',
        'parent_id',
        'parent_type',
        'parent_name',
        'parent_deleted_at',
        'root_id',
        'root_type',
        'root_name',
        'root_deleted_at',
        'deal_is_budget',
        'task_id',
        'deal_id',
        'booking_id',
        'invoice_id',
        'company_id',
        'created_at_api',
        'discussion_id',
        'engagement_id',
        'page_id',
        'person_id',
        'purchase_order_id',
        'made_by_automation',
        // Relationships
        'creator_id',
        'comment_id',
        'email_id',
        'attachment_id',
        // Arrays
        'roles'
    ];

    /**
     * Get the creator of the activity.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the comment associated with the activity.
     */
    public function comment()
    {
        return $this->belongsTo(ProductiveComment::class, 'comment_id');
    }

    /**
     * Get the email associated with the activity.
     */
    public function email()
    {
        return $this->belongsTo(ProductiveEmail::class, 'email_id');
    }

    /**
     * Get the attachment associated with the activity.
     */
    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }
}
