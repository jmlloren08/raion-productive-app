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
        Schema::create('productive_deal_statuses', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('deal_statuses'); // type of status, e.g., 'open', 'closed', etc.
            // Core attributes
            $table->string('name');
            $table->integer('position')->default(0);
            $table->unsignedBigInteger('color_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->boolean('time_tracking_enabled')->default(false);
            $table->boolean('expense_tracking_enabled')->default(false);
            $table->boolean('booking_tracking_enabled')->default(false);
            $table->unsignedBigInteger('status_id')->default(1);
            $table->boolean('probability_enabled')->default(false);
            $table->decimal('probability', 5, 2)->nullable();
            $table->boolean('lost_reason_enabled')->default(false);
            $table->boolean('used')->default(false);
            // Relationships
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('pipeline_id')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_deal_statuses');
    }
};
