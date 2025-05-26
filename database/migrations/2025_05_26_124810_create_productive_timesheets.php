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
        Schema::create('productive_timesheets', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('timesheets'); // type of timesheet, e.g., 'project', 'task', etc.
            // Core attributes
            $table->date('date')->index();
            $table->timestamp('created_at_api')->nullable(); // renamed to prevent conflict with Laravel's own timestamps
            // Relationships
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('person_id')->nullable();
            $table->foreignId('creator_id')->nullable();
           
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_timesheets');
    }
};
