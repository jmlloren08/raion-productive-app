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
        Schema::create('productive_projects', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('projects'); // type of project, e.g., 'project', 'task', etc.
            // Core attributes
            $table->string('name');
            $table->string('number')->nullable();
            $table->json('preferences')->nullable();
            $table->string('project_number')->nullable();
            $table->unsignedInteger('project_type_id')->nullable();
            $table->unsignedInteger('project_color_id')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('public_access')->default(false);
            $table->boolean('time_on_tasks')->default(false);
            $table->json('tag_colors')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->boolean('template')->default(false);
            $table->json('custom_fields')->nullable();
            $table->json('task_custom_fields_ids')->nullable();
            $table->json('task_custom_fields_positions')->nullable();
            $table->boolean('sample_data')->default(false);
            // Relationships
            $table->string('company_id')->nullable();
            $table->string('project_manager_id')->nullable();
            $table->string('last_actor_id')->nullable();
            $table->string('workflow_id')->nullable();
            
            $table->timestamps(); // Laravel's created_at and updated_at
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_projects');
    }
};
