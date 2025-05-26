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
        Schema::create('productive_boards', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('boards'); // type of board, e.g., 'kanban', 'scrum', etc.
            // Core attributes
            $table->string('name');
            $table->integer('position')->default(1);
            $table->integer('placement')->default(1010000);
            $table->timestamp('archived_at')->nullable();
            // Foreign keys without constraints - we'll add constraints in a separate migration
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('project_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_boards');
    }
};
