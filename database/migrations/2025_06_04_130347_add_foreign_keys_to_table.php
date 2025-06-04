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
        Schema::table('productive_projects', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('productive_companies');
            $table->foreign('project_manager_id')->references('id')->on('productive_people');
            $table->foreign('last_actor_id')->references('id')->on('productive_people');
            $table->foreign('workflow_id')->references('id')->on('productive_workflows');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['project_manager_id']);
            $table->dropForeign(['last_actor_id']);
            $table->dropForeign(['workflow_id']);
        });
    }
};
