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
            // Add foreign key constraints
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('productive_companies')->nullOnDelete();
            $table->foreign('project_manager_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('last_actor_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('workflow_id')->references('id')->on('productive_workflows')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_projects', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['project_manager_id']);
            $table->dropForeign(['last_actor_id']);
            $table->dropForeign(['workflow_id']);
        });
    }
};
