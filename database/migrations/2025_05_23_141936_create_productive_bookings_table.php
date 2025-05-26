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
            $table->id();
            $table->string('type')->default('bookings'); // type of booking, e.g., 'booking', 'time_entry', etc.
            // Core attributes
            $table->float('hours')->nullable();
            $table->integer('time');
            $table->date('started_on');
            $table->date('ended_on');
            $table->text('note')->nullable();
            $table->integer('total_time')->default(0);
            $table->integer('total_working_days')->default(0);
            $table->integer('percentage')->default(100);
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
            $table->string('stage_type')->nullable();
            // Relationships - using foreign IDs without constraints to avoid circular dependencies
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('service_id')->nullable();
            $table->foreignId('event_id')->nullable();
            $table->foreignId('person_id')->nullable();
            $table->foreignId('creator_id')->nullable();
            $table->foreignId('updater_id')->nullable();
            $table->foreignId('approver_id')->nullable();
            $table->foreignId('rejecter_id')->nullable();
            $table->foreignId('canceler_id')->nullable();
            $table->foreignId('origin_id')->nullable();
            $table->json('approval_statuses')->nullable();
            $table->foreignId('attachment_id')->nullable();
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
        Schema::dropIfExists('productive_bookings');
    }
};
