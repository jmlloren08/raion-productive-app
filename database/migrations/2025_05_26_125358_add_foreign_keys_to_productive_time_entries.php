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
        Schema::table('productive_time_entries', function (Blueprint $table) {
            // Adding foreign keys to the productive_time_entries table
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('productive_services')->nullOnDelete();
            $table->foreign('task_id')->references('id')->on('productive_tasks')->nullOnDelete();
            $table->foreign('deal_id')->references('id')->on('productive_deals')->nullOnDelete();
            $table->foreign('approver_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('updater_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('rejecter_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('last_actor_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('person_subsidiary_id')->references('id')->on('productive_subsidiaries')->nullOnDelete();
            $table->foreign('deal_subsidiary_id')->references('id')->on('productive_subsidiaries')->nullOnDelete();
            $table->foreign('timesheet_id')->references('id')->on('productive_timesheets')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_time_entries', function (Blueprint $table) {
            // Dropping foreign keys from the productive_time_entries table
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['task_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['approver_id']);
            $table->dropForeign(['updater_id']);
            $table->dropForeign(['rejecter_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['last_actor_id']);
            $table->dropForeign(['person_subsidiary_id']);
            $table->dropForeign(['deal_subsidiary_id']);
            $table->dropForeign(['timesheet_id']);
        });
    }
};
