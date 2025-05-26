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
        Schema::create('productive_events', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('events'); // type of event, e.g., 'meeting', 'call', etc.
            // Core attributes
            $table->string('name');
            $table->integer('event_type_id')->default(0); // Duration in minutes
            $table->string('icon_id')->nullable();
            $table->string('color_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->integer('limitation_type_id')->default(0);
            $table->boolean('sync_personal_integrations')->default(false);
            $table->boolean('half_day_bookings')->default(false);
            $table->text('description')->nullable();
            $table->string('absence_type')->nullable();
            // Relationships
            $table->foreignId('organization_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_events');
    }
};
