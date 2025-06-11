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
        Schema::create('productive_sections', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('sections'); // type of section, e.g., 'header', 'footer', etc.
            // Core attributes
            $table->string('name')->nullable();
            $table->json('preferences')->nullable();
            $table->integer('position')->default(1);
            $table->json('editor_config')->nullable();
            // Relationships
            $table->unsignedBigInteger('deal_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_sections');
    }
};
