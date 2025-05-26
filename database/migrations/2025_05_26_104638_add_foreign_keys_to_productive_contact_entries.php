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
        Schema::table('productive_contact_entries', function (Blueprint $table) {
            // Add foreign key constraints
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('productive_companies')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('productive_invoices')->nullOnDelete();
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_contact_entries', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['subsidiary_id']);
            $table->dropForeign(['purchase_order_id']);
        });
    }
};
