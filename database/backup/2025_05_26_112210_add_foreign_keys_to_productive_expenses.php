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
        Schema::table('productive_expenses', function (Blueprint $table) {
            // Adding foreign keys to the productive_expenses table
            // $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('deal_id')->references('id')->on('productive_deals')->nullOnDelete();
            $table->foreign('service_type_id')->references('id')->on('productive_service_types')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('approver_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('rejecter_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('productive_services')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders')->nullOnDelete();
            $table->foreign('tax_rate_id')->references('id')->on('productive_tax_rates')->nullOnDelete();
            $table->foreign('attachment_id')->references('id')->on('productive_attachments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_expenses', function (Blueprint $table) {
            // Dropping foreign keys from the productive_expenses table
            // $table->dropForeign(['organization_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['service_type_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['approver_id']);
            $table->dropForeign(['rejecter_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['tax_rate_id']);
            $table->dropForeign(['attachment_id']);
        });
    }
};
