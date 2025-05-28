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
        Schema::table('productive_subsidiaries', function (Blueprint $table) {
            // Add foreign key constraints
            // $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('bill_from_id')->references('id')->on('productive_contact_entries')->nullOnDelete();
            $table->foreign('custom_domain_id')->references('id')->on('productive_custom_domains')->nullOnDelete();
            $table->foreign('default_tax_rate_id')->references('id')->on('productive_tax_rates')->nullOnDelete();
            $table->foreign('integration_id')->references('id')->on('productive_integrations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_subsidiaries', function (Blueprint $table) {
            // Drop foreign key constraints
            // $table->dropForeign(['organization_id']);
            $table->dropForeign(['bill_from_id']);
            $table->dropForeign(['custom_domain_id']);
            $table->dropForeign(['default_tax_rate_id']);
            $table->dropForeign(['integration_id']);
        });
    }
};
