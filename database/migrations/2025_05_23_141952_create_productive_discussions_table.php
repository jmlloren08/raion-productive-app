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
            $table->id();
            $table->string('type')->default('discussions'); // type of discussion, e.g., 'email', 'chat', etc.
            // Core attributes
            $table->text('excerpt')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('subscriber_ids')->nullable();
            // Foreign keys without constraints - we'll add constraints in a separate migration
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('page_id')->nullable();
            $table->timestamps();
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
