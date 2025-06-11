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
        Schema::create('productive_task_lists', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('task_lists'); // type of task list, e.g., 'project', 'task', etc.
            // Core attributes
            $table->string('name');
            $table->integer('position')->nullable();
            $table->integer('placement');
            $table->timestamp('archived_at')->nullable();
            $table->string('email_key');
            // Relationships
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('board_id')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_task_lists');
    }
};
