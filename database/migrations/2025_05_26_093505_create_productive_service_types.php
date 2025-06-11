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
        Schema::create('productive_service_types', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('service_types'); // type of service, e.g., 'consulting', 'development', etc.
            // Core attributes
            $table->string('name')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('description')->nullable();
            // Relationships
            $table->unsignedBigInteger('assignee_id')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_service_types');
    }
};
