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
        Schema::create('productive_lost_reasons', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('lost_reasons'); // Type of lost reason, e.g., 'budget', 'scope', etc.
            // Core attributes
            $table->string('name');
            $table->timestamp('archived_at')->nullable();
            // Relationships
            $table->foreignId('organization_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_lost_reasons');
    }
};
