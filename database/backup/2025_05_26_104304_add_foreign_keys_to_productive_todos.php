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
        Schema::table('productive_todos', function (Blueprint $table) {
            // Add foreign key constraints
            // $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('assignee_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('deal_id')->references('id')->on('productive_deals')->nullOnDelete();
            $table->foreign('task_id')->references('id')->on('productive_tasks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_todos', function (Blueprint $table) {
            // Drop foreign key constraints
            // $table->dropForeign(['organization_id']);
            $table->dropForeign(['assignee_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['task_id']);
        });
    }
};
