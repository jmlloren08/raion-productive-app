<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveInvoiceAttribution extends Model
{
    use SoftDeletes;

    protected $table = 'productive_invoice_attributions';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'date_from',
        'date_to',
        'amount',
        'amount_default',
        'amount_normalized',
        'currency',
        'currency_default',
        'currency_normalized',
        // Relationships
        'invoice_id',
        'budget_id',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'amount' => 'integer',
        'amount_default' => 'integer',
        'amount_normalized' => 'integer',
        'currency' => 'string',
        'currency_default' => 'string',
        'currency_normalized' => 'string',
    ];

    /**
     * Get the invoice associated with the attribution.
     */
    public function invoice()
    {
        return $this->belongsTo(ProductiveInvoice::class, 'invoice_id');
    }

    /**
     * Get the budget associated with the attribution.
     */
    public function budget()
    {
        return $this->belongsTo(ProductiveDeal::class, 'budget_id');
    }
}
