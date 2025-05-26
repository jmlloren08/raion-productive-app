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
        Schema::table('productive_bills', function (Blueprint $table) {
            // Adding foreign keys to the productive_bills table
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deal_id')->references('id')->on('productive_deals')->nullOnDelete();
            $table->foreign('attachment_id')->references('id')->on('productive_attachments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_bills', function (Blueprint $table) {
            // Dropping foreign keys from the productive_bills table
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['attachment_id']);
        });
    }
};
