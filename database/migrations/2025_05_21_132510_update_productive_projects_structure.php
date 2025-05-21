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
            $table->id();
            
            // Foreign keys from relationships
            $table->foreignId('company_id')->nullable()->constrained('productive_companies')->nullOnDelete();     // relationship to companies table

            $table->string('type')->default('projects'); // type of project, e.g., 'project', 'task', etc.
            // Core project info
            $table->string('name');
            $table->string('number')->nullable(); // "22P1006"
            $table->string('project_number')->nullable(); // can be same as `number`, but included for compatibility

            // JSON-stored preferences and tag_colors
            $table->json('preferences')->nullable();
            $table->json('tag_colors')->nullable();

            // Related type info (you can normalize later)
            $table->unsignedBigInteger('project_type_id')->nullable();
            $table->unsignedBigInteger('project_color_id')->nullable();

            // Timestamps from API
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('created_at_api')->nullable(); // avoid Laravel collision

            // Flags
            $table->boolean('public_access')->default(false);
            $table->boolean('time_on_tasks')->default(false);
            $table->boolean('template')->default(false);
            $table->boolean('sample_data')->default(false);

            // JSON fields
            $table->json('custom_fields')->nullable();
            $table->json('task_custom_fields_ids')->nullable();
            $table->json('task_custom_fields_positions')->nullable();

            $table->timestamps(); // Laravel's created_at and updated_at
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
