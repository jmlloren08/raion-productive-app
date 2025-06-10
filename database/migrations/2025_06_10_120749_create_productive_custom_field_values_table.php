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
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('custom_field_id');
            $table->unsignedBigInteger('custom_field_option_id')->nullable();
            $table->string('custom_field_name');
            $table->string('custom_field_value')->nullable();
            $table->string('raw_value')->nullable(); // Store the original value from the API
            
            // Add foreign key constraints
            $table->foreign('project_id')->references('id')->on('productive_projects');
            $table->foreign('custom_field_id')->references('id')->on('productive_custom_fields');
            $table->foreign('custom_field_option_id')->references('id')->on('productive_cfos');
            
            // Add indexes for better query performance
            $table->index(['project_id', 'custom_field_id']);
            $table->index('custom_field_name');
            
            $table->timestamps();
            $table->softDeletes();
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
