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
        Schema::create('productive_tasks', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('tasks'); // type of task, e.g., 'task', 'subtask', etc.
            // Core attributes
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('number');
            $table->string('task_number');
            $table->boolean('private')->default(false);
            $table->date('due_date')->nullable();
            $table->date('start_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('created_at_api')->nullable(); // renamed to prevent conflict with Laravel's own timestamps
            $table->timestamp('updated_at_api')->nullable();
            $table->unsignedBigInteger('repeat_schedule_id')->nullable();
            $table->unsignedInteger('repeat_on_interval')->nullable();
            $table->unsignedInteger('repeat_on_monthday')->nullable();
            $table->json('repeat_on_weekday')->nullable();
            $table->date('repeat_on_date')->nullable();
            $table->unsignedBigInteger('repeat_origin_id')->nullable();
            $table->string('email_key');
            $table->json('custom_fields')->nullable();
            $table->unsignedInteger('todo_count')->nullable();
            $table->unsignedInteger('open_todo_count')->nullable();
            $table->unsignedInteger('subtask_count')->nullable();
            $table->unsignedInteger('open_subtask_count')->nullable();
            $table->unsignedBigInteger('creation_method_id');
            $table->json('todo_assignee_ids')->nullable();
            $table->unsignedInteger('task_dependency_count')->default(0);
            $table->unsignedBigInteger('type_id');
            $table->unsignedInteger('blocking_dependency_count')->default(0);
            $table->unsignedInteger('waiting_on_dependency_count')->default(0);
            $table->unsignedInteger('linked_dependency_count')->default(0);
            $table->unsignedInteger('placement');
            $table->unsignedInteger('subtask_placement')->nullable();
            $table->boolean('closed')->default(false);
            $table->time('due_time')->nullable();
            $table->json('tag_list')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->unsignedInteger('initial_estimate');
            $table->unsignedInteger('remaining_time');
            $table->unsignedInteger('billable_time')->nullable();
            $table->unsignedInteger('worked_time')->nullable();
            $table->timestamp('deleted_at_api')->nullable();
            // Relationships
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->unsignedBigInteger('last_actor_id')->nullable();
            $table->unsignedBigInteger('task_list_id')->nullable();
            $table->unsignedBigInteger('parent_task_id')->nullable();
            $table->unsignedBigInteger('workflow_status_id')->nullable();
            $table->json('repeated_task')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            // Arrays
            $table->json('custom_field_people')->nullable();
            $table->json('custom_field_attachments')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_tasks');
    }
};
