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
        Schema::create('productive_invoice_attributions', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('invoice_attributions');
            // Core attributes
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->integer('amount');
            $table->integer('amount_default');
            $table->integer('amount_normalized');
            $table->string('currency', 3);
            $table->string('currency_default', 3);
            $table->string('currency_normalized', 3);
            // Relationships
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('budget_id')->nullable();
           
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_invoice_attributions');
    }
};
