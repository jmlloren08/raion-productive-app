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
        Schema::create('productive_organizations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('type')->default('organizations'); // type of organization, e.g., 'company', 'team', etc.
            $table->string('name');
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_organizations');
    }
};
