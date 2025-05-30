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
        Schema::create('productive_time_entry_versions', function (Blueprint $table) {
            // Primary key
            $table->id('id')->primary();
            $table->string('type')->default('time_entry_versions'); // type of entry, e.g., 'time', 'expense', etc.
            // Basic attributes
            $table->string('event');
            $table->json('object_changes');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_type');
            $table->timestamp('created_at_api')->nullable();

            // Foreign keys (nullable to support partial data fetches)
            // $table->foreignId('time_entry_id')->nullable();
            $table->foreignId('creator_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_time_entry_versions');
    }
};
