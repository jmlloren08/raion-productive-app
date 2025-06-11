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
        Schema::create('productive_bookings', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('bookings'); // type of booking, e.g., 'booking', 'time_entry', etc.
            // Core attributes
            $table->float('hours')->nullable();
            $table->integer('time')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->text('note')->nullable();
            $table->integer('total_time')->nullable();
            $table->integer('total_working_days')->nullable();
            $table->integer('percentage')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->text('people_custom_fields')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamp('approved_at_api')->nullable();
            $table->boolean('rejected')->default(false);
            $table->text('rejected_reason')->nullable();
            $table->timestamp('rejected_at_api')->nullable();
            $table->boolean('canceled')->default(false);
            $table->timestamp('canceled_at_api')->nullable();
            $table->integer('booking_method_id')->default(1);
            $table->boolean('autotracking')->default(false);
            $table->boolean('draft')->default(false);
            $table->text('custom_fields')->nullable();
            $table->string('external_id')->nullable();
            $table->timestamp('last_activity_at_api')->nullable();
            $table->integer('stage_type')->nullable();
            // Relationships
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('updater_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedBigInteger('rejecter_id')->nullable();
            $table->unsignedBigInteger('canceler_id')->nullable();
            $table->unsignedBigInteger('origin_id')->nullable();
            $table->unsignedBigInteger('approval_status_id')->nullable();
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
        Schema::dropIfExists('productive_bookings');
    }
};
