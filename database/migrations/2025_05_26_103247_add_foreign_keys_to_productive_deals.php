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
        Schema::table('productive_deals', function (Blueprint $table) {
            // Adding foreign keys to the productive_deals table
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('document_type_id')->references('id')->on('productive_document_types')->nullOnDelete();
            $table->foreign('responsible_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('deal_status_id')->references('id')->on('productive_deal_statuses')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('productive_projects')->nullOnDelete();
            $table->foreign('lost_reason_id')->references('id')->on('productive_lost_reasons')->nullOnDelete();
            $table->foreign('contract_id')->references('id')->on('productive_contracts')->nullOnDelete();
            $table->foreign('contact_id')->references('id')->on('productive_contact_entries')->nullOnDelete();
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries')->nullOnDelete();
            $table->foreign('tax_rate_id')->references('id')->on('productive_tax_rates')->nullOnDelete();
            $table->foreign('pipeline_id')->references('id')->on('productive_pipelines')->nullOnDelete();
            $table->foreign('apa_id')->references('id')->on('productive_apa')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_deals', function (Blueprint $table) {
            // Dropping foreign keys from the productive_deals table
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['document_type_id']);
            $table->dropForeign(['responsible_id']);
            $table->dropForeign(['deal_status_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['lost_reason_id']);
            $table->dropForeign(['contract_id']);
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['subsidiary_id']);
            $table->dropForeign(['tax_rate_id']);
            $table->dropForeign(['pipeline_id']);
            $table->dropForeign(['apa_id']);
        });
    }
};
