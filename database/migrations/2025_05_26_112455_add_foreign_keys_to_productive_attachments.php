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
        Schema::table('productive_attachments', function (Blueprint $table) {
            // Add foreign key constraints
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('productive_invoices')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders')->nullOnDelete();
            $table->foreign('bill_id')->references('id')->on('productive_bills')->nullOnDelete();
            $table->foreign('email_id')->references('id')->on('productive_emails')->nullOnDelete();
            $table->foreign('page_id')->references('id')->on('productive_pages')->nullOnDelete();
            $table->foreign('expense_id')->references('id')->on('productive_expenses')->nullOnDelete();
            $table->foreign('comment_id')->references('id')->on('productive_comments')->nullOnDelete();
            $table->foreign('task_id')->references('id')->on('productive_tasks')->nullOnDelete();
            $table->foreign('document_style_id')->references('id')->on('productive_document_styles')->nullOnDelete();
            $table->foreign('document_type_id')->references('id')->on('productive_document_types')->nullOnDelete();
            $table->foreign('deal_id')->references('id')->on('productive_deals')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_attachments', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['bill_id']);
            $table->dropForeign(['email_id']);
            $table->dropForeign(['page_id']);
            $table->dropForeign(['expense_id']);
            $table->dropForeign(['comment_id']);
            $table->dropForeign(['task_id']);
            $table->dropForeign(['document_style_id']);
            $table->dropForeign(['document_type_id']);
            $table->dropForeign(['deal_id']);
        });
    }
};
