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
        Schema::create('productive_time_entries', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('time_entries'); // type of entry, e.g., 'time', 'expense', etc.
            // Basic attributes
            $table->date('date')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->integer('time')->nullable();
            $table->integer('billable_time')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('track_method_id')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('timer_started_at')->nullable();
            $table->datetime('timer_stopped_at')->nullable();
            $table->boolean('approved')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->unsignedBigInteger('calendar_event_id')->nullable();
            $table->foreignId('invoice_attribution_id')->nullable();
            $table->boolean('invoiced')->nullable();
            $table->boolean('overhead')->nullable();
            $table->boolean('rejected')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->datetime('last_activity_at')->nullable();
            $table->boolean('submitted')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('currency_default', 3)->nullable();
            $table->string('currency_normalized', 3)->nullable();

            // Foreign keys (nullable to support partial data fetches)
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedBigInteger('updater_id')->nullable();
            $table->unsignedBigInteger('rejecter_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('last_actor_id')->nullable();
            $table->unsignedBigInteger('person_subsidiary_id')->nullable();
            $table->unsignedBigInteger('deal_subsidiary_id')->nullable();
            $table->unsignedBigInteger('timesheet_id')->nullable();

            $table->timestamps();

            // Soft delete support
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_time_entries');
    }
};
