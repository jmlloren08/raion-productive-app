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
        Schema::create('productive_expenses', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('expenses'); // type of expense, e.g., 'travel', 'supplies', etc.
            // Core attributes
            $table->string('name');
            $table->date('date');
            $table->date('pay_on')->nullable();
            $table->date('paid_on')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('invoiced')->default(false);
            $table->boolean('approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->boolean('rejected')->default(false);
            $table->string('rejected_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('deleted_at_api')->nullable();
            $table->boolean('reimbursable')->default(false);
            $table->date('reimbursed_on')->nullable();
            $table->decimal('exchange_rate', 10, 2);
            $table->decimal('exchange_rate_normalized', 10, 2);
            $table->date('exchange_date');
            $table->timestamp('created_at_api')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('quantity_received', 10, 2);
            $table->json('custom_fields')->nullable();
            $table->boolean('draft')->default(false);
            $table->boolean('exported')->default(false);
            $table->timestamp('exported_at')->nullable();
            $table->integer('export_integration_type_id')->nullable();
            $table->integer('export_id')->nullable();
            $table->text('export_url')->nullable();
            $table->integer('company_reference_id')->nullable();
            $table->string('external_payment_id')->nullable();
            $table->string('currency')->default('AUD');
            $table->string('currency_default')->default('AUD');
            $table->string('currency_normalized')->default('AUD');
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_default', 10, 2);
            $table->decimal('amount_normalized', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('total_amount_default', 10, 2);
            $table->decimal('total_amount_normalized', 10, 2);
            $table->decimal('billable_amount', 10, 2);
            $table->decimal('billable_amount_default', 10, 2);
            $table->decimal('billable_amount_normalized', 10, 2);
            $table->decimal('profit', 10, 2)->default(0);
            $table->decimal('profit_default', 10, 2)->default(0);
            $table->decimal('profit_normalized', 10, 2)->default(0);
            $table->decimal('recognized_revenue', 10, 2);
            $table->decimal('recognized_revenue_default', 10, 2);
            $table->decimal('recognized_revenue_normalized', 10, 2);
            $table->decimal('amount_with_tax', 10, 2);
            $table->decimal('amount_with_tax_default', 10, 2);
            $table->decimal('amount_with_tax_normalized', 10, 2);
            $table->decimal('total_amount_with_tax', 10, 2);
            $table->decimal('total_amount_with_tax_default', 10, 2);
            $table->decimal('total_amount_with_tax_normalized', 10, 2);
            // Relationships
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('deal_id')->nullable();
            $table->foreignId('service_type_id')->nullable();
            $table->foreignId('person_id')->nullable();
            $table->foreignId('creator_id')->nullable();
            $table->foreignId('approver_id')->nullable();
            $table->foreignId('rejecter_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('service_id')->nullable();
            $table->foreignId('purchase_order_id')->nullable();
            $table->foreignId('tax_rate_id')->nullable();
            $table->foreignId('attachment_id')->nullable();
            
            $table->json('custom_field_people')->nullable();
            $table->json('custom_field_attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_expenses');
    }
};
