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
        Schema::create('productive_pipelines', function (Blueprint $table) {
            // Primary key
            $table->id('id')->primary();
            $table->string('type')->default('pipelines');
            // Core attributes
            $table->string('name');
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->integer('position');
            $table->string('icon_id');
            $table->integer('pipeline_type_id');

            // Relationships
            $table->string('creator_id')->nullable();
            $table->string('updater_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_pipelines');
    }
};
