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
        Schema::create('productive_workflows', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('workflow'); // type of workflow, e.g., 'project', 'task', etc.
            // Core attributes
            $table->string('name');
            $table->timestamp('archived_at')->nullable();
            // Relationships
            $table->unsignedBigInteger('workflow_status_id')->nullable();
           
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_workflows');
    }
};
