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
        Schema::create('productive_surveys', function (Blueprint $table) {

            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('surveys'); // Default type for surveys

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('public_uuid')->nullable();
            $table->string('submission_access')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('updated_at_api')->nullable();

            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('updater_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_surveys');
    }
};
