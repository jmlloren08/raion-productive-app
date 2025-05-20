<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productive_companies', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->timestamp('productive_created_at');
            $table->timestamp('productive_updated_at');
            $table->timestamps();
        });

        Schema::create('productive_projects', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('name');
            $table->string('status');
            $table->timestamp('productive_created_at');
            $table->timestamp('productive_updated_at');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('productive_companies')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productive_projects');
        Schema::dropIfExists('productive_companies');
    }
};
