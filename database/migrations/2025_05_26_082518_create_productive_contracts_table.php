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
            $table->id();
            $table->string('type')->default('contract'); // Type of contract, e.g., 'service', 'employment', etc.
            // Core attributes
            $table->date('ends_on')->nullable();
            $table->date('starts_on');
            $table->date('next_occurrence_on');
            $table->unsignedBigInteger('interval_id');
            $table->boolean('copy_purchase_order_number')->default(false);
            $table->boolean('copy_expenses')->default(false);
            $table->boolean('use_rollover_hours')->default(false);
            // Foreign keys for relationships
            $table->foreignId('organization_id')->nullable();

            $table->json('template')->nullable(); 

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
