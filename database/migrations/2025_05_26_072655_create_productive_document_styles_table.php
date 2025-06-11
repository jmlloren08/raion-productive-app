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
        Schema::create('productive_document_styles', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('document_styles'); // type of document style, e.g., 'default', 'invoice', etc.
            // Core attributes
            $table->string('name');
            $table->json('styles')->nullable(); // We'll fill this with default values in a seeder
            
            $table->unsignedBigInteger('attachment_id')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_document_styles');
    }
};
