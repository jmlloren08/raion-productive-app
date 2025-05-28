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
        Schema::table('productive_invoice_attributions', function (Blueprint $table) {
            // Add foreign key constraints
            // $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('productive_invoices')->nullOnDelete();
            $table->foreign('budget_id')->references('id')->on('productive_deals')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_invoice_attributions', function (Blueprint $table) {
            // Drop foreign key constraints
            // $table->dropForeign(['organization_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['budget_id']);
        });
    }
};
