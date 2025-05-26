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
        Schema::table('productive_comments', function (Blueprint $table) {
            // Add foreign key constraints
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('productive_companies')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('deal_id')->references('id')->on('productive_deals')->nullOnDelete();
            $table->foreign('discussion_id')->references('id')->on('productive_discussions')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('productive_invoices')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('pinned_by_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('task_id')->references('id')->on('productive_tasks')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders')->nullOnDelete();
            $table->foreign('attachment_id')->references('id')->on('productive_attachments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_comments', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['discussion_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['pinned_by_id']);
            $table->dropForeign(['task_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['attachment_id']);
        });
    }
};
