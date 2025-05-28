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
        Schema::create('productive_tax_rates', function (Blueprint $table) {
            // Primary key
            $table->string('id')->primary();
            $table->string('type')->default('tax_rates');
            // Core attributes
            $table->string('name');
            $table->string('primary_component_name')->nullable();
            $table->decimal('primary_component_value', 10, 2)->nullable();
            $table->string('secondary_component_name')->nullable();
            $table->decimal('secondary_component_value', 10, 2)->nullable();
            $table->timestamp('archived_at')->nullable();
            // Relationships
            $table->foreignId('subsidiary_id')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_tax_rates');
    }
};
