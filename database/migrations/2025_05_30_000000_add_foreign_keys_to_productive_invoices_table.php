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
        Schema::table('productive_invoices', function (Blueprint $table) {
            // Add foreign key constraints
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('bill_to_id')->references('id')->on('productive_contact_entries')->nullOnDelete();
            $table->foreign('bill_from_id')->references('id')->on('productive_contact_entries')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('productive_companies')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('parent_invoice_id')->references('id')->on('productive_invoices')->nullOnDelete();
            $table->foreign('issuer_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('invoice_attribution_id')->references('id')->on('productive_invoice_attributions')->nullOnDelete();
            $table->foreign('attachment_id')->references('id')->on('productive_attachments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_invoices', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['bill_to_id']);
            $table->dropForeign(['bill_from_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['parent_invoice_id']);
            $table->dropForeign(['issuer_id']);
            $table->dropForeign(['invoice_attribution_id']);
            $table->dropForeign(['attachment_id']);
        });
    }
};