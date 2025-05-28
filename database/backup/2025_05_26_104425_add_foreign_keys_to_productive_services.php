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
        Schema::table('productive_services', function (Blueprint $table) {
            // Add foreign key constraints
            // $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('service_type_id')->references('id')->on('productive_service_types')->nullOnDelete();
            $table->foreign('deal_id')->references('id')->on('productive_deals')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('section_id')->references('id')->on('productive_sections')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_services', function (Blueprint $table) {
            // Drop foreign key constraints
            // $table->dropForeign(['organization_id']);
            $table->dropForeign(['service_type_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['section_id']);
        });
    }
};
