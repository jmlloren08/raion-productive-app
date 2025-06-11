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
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->integer('repeat_schedule_id')->nullable();
            $table->integer('repeat_on_interval')->nullable();
            $table->unsignedInteger('repeat_on_monthday')->nullable();
            $table->json('repeat_on_weekday')->nullable();
            $table->date('repeat_on_date')->nullable();
            $table->integer('repeat_origin_id')->nullable();
            $table->string('email_key');
            $table->json('custom_fields')->nullable();
            $table->integer('todo_count')->nullable();
            $table->integer('open_todo_count')->nullable();
            $table->integer('subtask_count')->nullable();
            $table->integer('open_subtask_count')->nullable();
            $table->integer('creation_method_id');
            $table->json('todo_assignee_ids')->nullable();
            $table->integer('task_dependency_count')->default(0);
            $table->integer('type_id');
            $table->integer('blocking_dependency_count')->default(0);
            $table->integer('waiting_on_dependency_count')->default(0);
            $table->integer('linked_dependency_count')->default(0);

            $table->integer('placement')->unsigned()->nullable();
            $table->integer('subtask_placement')->nullable();
            $table->boolean('closed')->default(false);
            $table->string('due_time')->nullable();
            $table->json('tag_list')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('initial_estimate')->nullable();
            $table->integer('remaining_time')->nullable();
            $table->integer('billable_time')->nullable();
            $table->integer('worked_time')->nullable();
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
