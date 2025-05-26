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
            // Add foreign key constraints
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('default_subsidiary_id')->references('id')->on('productive_subsidiaries')->nullOnDelete();
            $table->foreign('default_tax_rate_id')->references('id')->on('productive_tax_rates')->nullOnDelete();
            $table->foreign('default_document_type_id')->references('id')->on('productive_document_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_companies', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['default_subsidiary_id']);
            $table->dropForeign(['default_tax_rate_id']);
            $table->dropForeign(['default_document_type_id']);
        });
    }
};
