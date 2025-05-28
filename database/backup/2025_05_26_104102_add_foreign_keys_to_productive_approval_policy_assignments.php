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
        Schema::table('productive_apa', function (Blueprint $table) {
            // Add foreign key constraints
            // $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('deal_id')->references('id')->on('productive_deals')->nullOnDelete();
            $table->foreign('approval_policy_id')->references('id')->on('productive_approval_policies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_apa', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['approval_policy_id']);
        });
    }
};
