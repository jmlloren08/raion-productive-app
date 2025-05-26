<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productive_invoices', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('invoices'); // type of invoice, e.g., 'invoice', 'credit_note', etc.
            // Core attributes
            $table->string('number')->nullable();
            $table->string('subject')->nullable();
            $table->date('invoiced_on')->nullable();
            $table->date('sent_on')->nullable();
            $table->date('pay_on')->nullable();
            $table->date('delivery_on')->nullable();
            $table->date('paid_on')->nullable();
            $table->date('finalized_on')->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->string('tax1_name')->nullable();
            $table->decimal('tax1_value', 10, 2)->nullable();
            $table->string('tax2_name')->nullable();
            $table->decimal('tax2_value', 10, 2)->nullable();
            $table->timestamp('deleted_at_api')->nullable();
            $table->string('tag_list')->nullable();
            $table->text('note')->nullable();
            $table->boolean('exported')->default(false);
            $table->timestamp('exported_at')->nullable();
            $table->integer('export_integration_type_id')->nullable();
            $table->string('export_id')->nullable();
            $table->string('export_invoice_url')->nullable();
            $table->string('company_reference_id')->nullable();
            $table->text('note_interpolated')->nullable();
            $table->string('email_key')->nullable();
            $table->string('purchase_order_number')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->decimal('exchange_rate', 10, 2)->nullable();
            $table->date('exchange_date')->nullable();
            $table->text('custom_fields')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->boolean('sample_data')->default(false);
            $table->boolean('pay_on_relative')->default(false);
            $table->integer('invoice_type_id')->nullable();
            $table->boolean('credited')->default(false);
            $table->boolean('line_item_tax')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->json('creation_options')->nullable();
            $table->integer('payment_terms')->nullable();
            $table->string('currency')->nullable();
            $table->string('currency_default')->nullable();
            $table->string('currency_normalized')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('amount_default', 10, 2)->nullable();
            $table->decimal('amount_normalized', 10, 2)->nullable();
            $table->decimal('amount_tax', 10, 2)->nullable();
            $table->decimal('amount_tax_default', 10, 2)->nullable();
            $table->decimal('amount_tax_normalized', 10, 2)->nullable();
            $table->decimal('amount_with_tax', 10, 2)->nullable();
            $table->decimal('amount_with_tax_default', 10, 2)->nullable();
            $table->decimal('amount_with_tax_normalized', 10, 2)->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->decimal('amount_paid_default', 10, 2)->nullable();
            $table->decimal('amount_paid_normalized', 10, 2)->nullable();
            $table->decimal('amount_written_off', 10, 2)->nullable();
            $table->decimal('amount_written_off_default', 10, 2)->nullable();
            $table->decimal('amount_written_off_normalized', 10, 2)->nullable();
            $table->decimal('amount_unpaid', 10, 2)->nullable();
            $table->decimal('amount_unpaid_default', 10, 2)->nullable();
            $table->decimal('amount_unpaid_normalized', 10, 2)->nullable();
            $table->decimal('amount_credited', 10, 2)->nullable();
            $table->decimal('amount_credited_default', 10, 2)->nullable();
            $table->decimal('amount_credited_normalized', 10, 2)->nullable();
            $table->decimal('amount_credited_with_tax', 10, 2)->nullable();
            $table->decimal('amount_credited_with_tax_default', 10, 2)->nullable();
            $table->decimal('amount_credited_with_tax_normalized', 10, 2)->nullable();
            // Foreign keys without constraints - we'll add constraints in a separate migration
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('bill_to_id')->nullable();
            $table->unsignedBigInteger('bill_from_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('subsidiary_id')->nullable();
            $table->unsignedBigInteger('parent_invoice_id')->nullable();
            $table->unsignedBigInteger('issuer_id')->nullable();
            $table->unsignedBigInteger('invoice_attribution_id')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            // Arrays
            $table->json('custom_field_people')->nullable();
            $table->json('custom_field_attachments')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_invoices');
    }
};
