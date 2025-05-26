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
        Schema::create('productive_todos', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('todos'); // type of todo, e.g., 'todo', 'task', etc.
            // Core attributes
            $table->string('description');
            $table->timestamp('closed_at')->nullable();
            $table->boolean('closed')->default(false);
            $table->date('due_date')->nullable();
            $table->timestamp('created_at_api')->nullable(); // renamed to prevent conflict with Laravel's own timestamps
            $table->string('todoable_type');
            $table->time('due_time')->nullable();
            $table->integer('position')->default(0);
            // Relationships
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('assignee_id')->nullable();
            $table->foreignId('deal_id')->nullable();
            $table->foreignId('task_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_todos');
    }
};
