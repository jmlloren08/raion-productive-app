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
        Schema::create('productive_approval_policies', function (Blueprint $table) {
            // Primary key
            $table->id('id')->primary();
            $table->string('type')->default('approval_policies');
            // Core attributes
            $table->timestamp('archived_at')->nullable();
            $table->boolean('custom')->default(false);
            $table->boolean('default')->default(false);
            $table->text('description')->nullable();
            $table->string('name');
            $table->integer('type_id')->default(0);

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_approval_policies');
    }
};
