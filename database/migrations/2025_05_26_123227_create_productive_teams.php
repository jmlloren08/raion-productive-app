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
        Schema::create('productive_teams', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('teams'); // type of team, e.g., 'project', 'department', etc.
            // Core attributes
            $table->unsignedBigInteger('color_id')->default(0);
            $table->unsignedBigInteger('icon_id')->nullable();
            $table->string('name');
            // Relationships
            $table->foreignId('organization_id')->nullable();
            
            $table->json('members_included')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_teams');
    }
};
