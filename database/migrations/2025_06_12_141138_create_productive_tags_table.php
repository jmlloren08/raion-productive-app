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
        Schema::create('productive_tags', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('tags');
            $table->string('name')->nullable();
            $table->string('color')->nullable();
            
            // Add indexes for better query performance
            $table->index(['tag_id', 'type']);
            $table->index('name');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_tags');
    }
};
