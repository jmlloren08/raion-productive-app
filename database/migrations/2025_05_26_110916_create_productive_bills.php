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
        Schema::create('productive_bills', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('bills'); // type of bill, e.g., 'invoice', 'receipt', etc.
            // Core attributes
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at_api')->nullable(); // renamed to prevent conflict with Laravel's own timestamps
            $table->string('currency')->nullable(); // type of billable entity, e.g., 'client', 'vendor', etc.
            $table->string('currency_default')->nullable();
            $table->string('currency_normalized')->nullable();
            $table->decimal('total_received')->nullable();
            $table->decimal('total_received_default')->nullable();
            $table->decimal('total_received_normalized')->nullable();
            $table->decimal('total_cost')->nullable();
            $table->decimal('total_cost_default')->nullable();
            $table->decimal('total_cost_normalized')->nullable();

            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_bills');
    }
};
