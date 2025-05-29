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
        Schema::create('productive_contracts', function (Blueprint $table) {
            // Primary key
            $table->id('id')->primary();
            $table->string('type')->default('contracts'); // Type of contract, e.g., 'service', 'employment', etc.
            // Core attributes
            $table->date('ends_on')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('next_occurrence_on')->nullable();
            $table->unsignedBigInteger('interval_id')->nullable();
            $table->boolean('copy_purchase_order_number')->default(false);
            $table->boolean('copy_expenses')->default(false);
            $table->boolean('use_rollover_hours')->default(false);

            $table->string('deal_id')->nullable(); // JSON field for template data

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_contracts');
    }
};
