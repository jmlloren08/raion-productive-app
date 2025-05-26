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
        Schema::table('productive_bookings', function (Blueprint $table) {
            // Add foreign key constraints
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('productive_services')->nullOnDelete();
            $table->foreign('event_id')->references('id')->on('productive_events')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('updater_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('approver_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('rejecter_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('canceler_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('origin_id')->references('id')->on('productive_bookings')->nullOnDelete();
            $table->foreign('attachment_id')->references('id')->on('productive_attachments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_bookings', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['event_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['updater_id']);
            $table->dropForeign(['approver_id']);
            $table->dropForeign(['rejecter_id']);
            $table->dropForeign(['canceler_id']);
            $table->dropForeign(['origin_id']);
            $table->dropForeign(['attachment_id']);
        });
    }
};
