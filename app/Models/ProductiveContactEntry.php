<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductiveContactEntry extends Model
{
    use SoftDeletes;
    
    protected $table = 'productive_contact_entries';

    public $incrementing = false;
    public $timestamps = false; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'type',
        'contactable_type',
        'type_name',
        'name',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'zipcode',
        'country',
        'vat',
        'billing_address',
        
        'company_id',
        'person_id',
        'invoice_id',
        'subsidiary_id',
        'purchase_order_id'
    ];

    /**
     * Get the company associated with the contact entry.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(ProductiveCompany::class, 'company_id');
    }

    /**
     * Get the person associated with the contact entry.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(ProductivePeople::class, 'person_id');
    }

    /**
     * Get the invoice associated with the contact entry.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ProductiveInvoice::class, 'invoice_id');
    }

    /**
     * Get the subsidiary associated with the contact entry.
     */
    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'subsidiary_id');
    }

    /**
     * Get the purchase order associated with the contact entry.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(ProductivePurchaseOrder::class, 'purchase_order_id');
    }
}
