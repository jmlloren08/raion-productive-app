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
        Schema::create('productive_discussions', function (Blueprint $table) {
            // Primary key
            $table->id('id')->primary();
            $table->string('type')->default('discussions'); // type of discussion, e.g., 'email', 'chat', etc.
            // Core attributes
            $table->text('excerpt')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('subscriber_ids')->nullable();
            // Relationships    
            $table->string('page_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_discussions');
    }
};
