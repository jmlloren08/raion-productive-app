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
        Schema::create('productive_workflow_statuses', function (Blueprint $table) {
            // Primary key
            $table->string('id')->primary();
            $table->string('type')->default('workflow_statuses'); // type of workflow status, e.g., 'draft', 'published', etc.
            // Core attributes
            $table->string('name');
            $table->integer('color_id')->default(0);
            $table->integer('position')->default(0);
            $table->integer('category_id')->default(0);
            // Relationships
            $table->string('workflow_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_workflow_statuses');
    }
};
