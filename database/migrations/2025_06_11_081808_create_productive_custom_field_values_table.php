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
        Schema::create('productive_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_id'); // Changed from project_id to entity_id for polymorphic relationship
            $table->string('entity_type'); // Added to support polymorphic relationships
            // Original columns
            $table->unsignedBigInteger('custom_field_id');
            $table->unsignedBigInteger('custom_field_option_id')->nullable();
            $table->string('custom_field_name');
            $table->string('custom_field_value')->nullable();
            $table->string('raw_value')->nullable(); // Store the original value from the API
            
            // Add foreign key constraints
            $table->foreign('custom_field_id')->references('id')->on('productive_custom_fields');
            $table->foreign('custom_field_option_id')->references('id')->on('productive_cfos');
            
            // Add indexes for better query performance
            $table->index(['entity_id', 'entity_type']);
            $table->index('custom_field_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_custom_field_values');
    }
};
