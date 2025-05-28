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
        Schema::table('productive_tasks', function (Blueprint $table) {
            // Add foreign key constraints
            // $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('productive_projects')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('assignee_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('last_actor_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('task_list_id')->references('id')->on('productive_task_lists')->nullOnDelete();
            $table->foreign('parent_task_id')->references('id')->on('productive_tasks')->nullOnDelete();
            $table->foreign('workflow_status_id')->references('id')->on('productive_workflow_statuses')->nullOnDelete();
            $table->foreign('attachment_id')->references('id')->on('productive_attachments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_tasks', function (Blueprint $table) {
            // Drop foreign key constraints
            // $table->dropForeign(['organization_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['assignee_id']);
            $table->dropForeign(['last_actor_id']);
            $table->dropForeign(['task_list_id']);
            $table->dropForeign(['parent_task_id']);
            $table->dropForeign(['workflow_status_id']);
            $table->dropForeign(['attachment_id']);
        });
    }
};
