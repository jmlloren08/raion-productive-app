<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveInvoice extends Model
{
    protected $table = 'productive_invoices';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'number',
        'subject',
        'invoiced_on',
        'sent_on',
        'pay_on',
        'delivery_on',
        'paid_on',
        'finalized_on',
        'discount',
        'tax1_name',
        'tax1_value',
        'tax2_name',
        'tax2_value',
        'deleted_at_api',
        'tag_list',
        'note',
        'exported',
        'exported_at',
        'export_integration_type_id',
        'export_id',
        'export_invoice_url',
        'company_reference_id',
        'note_interpolated',
        'email_key',
        'purchase_order_number',
        'created_at_api',
        'exchange_rate',
        'exchange_date',
        'custom_fields',
        'updated_at_api',
        'sample_data',
        'pay_on_relative',
        'invoice_type_id',
        'credited',
        'line_item_tax',
        'last_activity_at',
        'creation_options',
        'payment_terms',
        'currency',
        'currency_default',
        'currency_normalized',
        'amount',
        'amount_default',
        'amount_normalized',
        'amount_tax',
        'amount_tax_default',
        'amount_tax_normalized',
        'amount_with_tax',
        'amount_with_tax_default',
        'amount_with_tax_normalized',
        'amount_paid',
        'amount_paid_default',
        'amount_paid_normalized',
        'amount_written_off',
        'amount_written_off_default',
        'amount_written_off_normalized',
        'amount_unpaid',
        'amount_unpaid_default',
        'amount_unpaid_normalized',
        'amount_credited',
        'amount_credited_default',
        'amount_credited_normalized',
        'amount_credited_with_tax',
        'amount_credited_with_tax_default',
        'amount_credited_with_tax_normalized',
        // Relationships
        'bill_to_id',
        'bill_from_id',
        'company_id',
        'document_type_id',
        'creator_id',
        'subsidiary_id',
        'parent_invoice_id',
        'issuer_id',
        'invoice_attribution_id',
        'attachment_id',
        // Arrays
        'custom_field_people',
        'custom_field_attachments',
    ];

    protected $casts = [
        'tag_list' => 'array',
        'custom_fields' => 'array',
        'creation_options' => 'array',
        'custom_field_people' => 'array',
        'custom_field_attachments' => 'array',
    ];

    public function billTo()
    {
        return $this->belongsTo(ProductiveContactEntry::class, 'bill_to_id');
    }

    public function billFrom()
    {
        return $this->belongsTo(ProductiveContactEntry::class, 'bill_from_id');
    }

    public function company()
    {
        return $this->belongsTo(ProductiveCompany::class, 'company_id');
    }

    public function documentType()
    {
        return $this->belongsTo(ProductiveDocumentType::class, 'document_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    public function subsidiary()
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'subsidiary_id');
    }

    public function parentInvoice()
    {
        return $this->belongsTo(ProductiveInvoice::class, 'parent_invoice_id');
    }

    public function issuer()
    {
        return $this->belongsTo(ProductivePeople::class, 'issuer_id');
    }

    public function invoiceAttribution()
    {
        return $this->belongsTo(ProductiveInvoiceAttribution::class, 'invoice_attribution_id');
    }

    public function attachment()
    {
        return $this->belongsTo(ProductiveAttachment::class, 'attachment_id');
    }
}
