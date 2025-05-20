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
        Schema::table('productive_companies', function (Blueprint $table) {
            $table->timestamp('productive_created_at')->nullable();
            $table->timestamp('productive_updated_at')->nullable();
        });

        Schema::table('productive_projects', function (Blueprint $table) {
            $table->timestamp('productive_created_at')->nullable();
            $table->timestamp('productive_updated_at')->nullable();
        });

        Schema::table('productive_deals', function (Blueprint $table) {
            $table->timestamp('productive_created_at')->nullable();
            $table->timestamp('productive_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_companies', function (Blueprint $table) {
            $table->dropColumn(['productive_created_at', 'productive_updated_at']);
        });

        Schema::table('productive_projects', function (Blueprint $table) {
            $table->dropColumn(['productive_created_at', 'productive_updated_at']);
        });

        Schema::table('productive_deals', function (Blueprint $table) {
            $table->dropColumn(['productive_created_at', 'productive_updated_at']);
        });
    }
};
