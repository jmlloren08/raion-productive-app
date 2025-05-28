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
        Schema::create('productive_purchase_orders', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('purchase_orders'); // type of purchase order, e.g., 'purchase_order', 'credit_note', etc.
            // Core attributes
            $table->string('subject')->nullable();
            $table->integer('status_id')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('delivery_on')->nullable();
            $table->date('sent_on')->nullable();
            $table->date('received_on')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->string('number')->nullable();
            $table->text('note')->nullable();
            $table->text('note_interpolated')->nullable();
            $table->string('email_key')->nullable();
            $table->integer('payment_status_id')->nullable();
            $table->decimal('exchange_rate', 16, 4)->nullable();
            $table->date('exchange_date')->nullable();
            $table->string('currency')->nullable();
            $table->string('currency_default')->nullable();
            $table->string('currency_normalized')->nullable();
            $table->decimal('total_cost', 16, 4)->nullable();
            $table->decimal('total_cost_default', 16, 4)->nullable();
            $table->decimal('total_cost_normalized', 16, 4)->nullable();
            $table->decimal('total_cost_with_tax', 16, 4)->nullable();
            $table->decimal('total_cost_with_tax_default', 16, 4)->nullable();
            $table->decimal('total_cost_with_tax_normalized', 16, 4)->nullable();
            $table->decimal('total_received', 16, 4)->nullable();
            $table->decimal('total_received_default', 16, 4)->nullable();
            $table->decimal('total_received_normalized', 16, 4)->nullable();
            // Foreign keys without constraints - we'll add constraints in a separate migration
            $table->foreignId('organization_id')->nullable();
            $table->json('vendor')->nullable();
            $table->string('deal_id')->nullable();
            $table->foreignId('creator_id')->nullable();
            $table->foreignId('document_type_id')->nullable();
            $table->foreignId('attachment_id')->nullable();
            $table->foreignId('bill_to_id')->nullable();
            $table->foreignId('bill_from_id')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_purchase_orders');
    }
};
