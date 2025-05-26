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
        Schema::table('productive_activities', function (Blueprint $table) {
            // Add foreign key constraints
            $table->foreign('organization_id')->references('id')->on('productive_organizations')->nullOnDelete();
            $table->foreign('item_id')->references('id')->on('productive_todos')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('productive_tasks')->nullOnDelete();
            $table->foreign('root_id')->references('id')->on('productive_projects')->nullOnDelete();
            $table->foreign('booking_id')->references('id')->on('productive_bookings')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('productive_invoices')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('productive_companies')->nullOnDelete();
            $table->foreign('discussion_id')->references('id')->on('productive_discussions')->nullOnDelete();
            $table->foreign('page_id')->references('id')->on('productive_pages')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders')->nullOnDelete();
            $table->foreign('creator_id')->references('id')->on('productive_people')->nullOnDelete();
            $table->foreign('comment_id')->references('id')->on('productive_comments')->nullOnDelete();
            $table->foreign('email_id')->references('id')->on('productive_emails')->nullOnDelete();
            $table->foreign('attachment_id')->references('id')->on('productive_attachments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_activities', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['root_id']);
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['discussion_id']);
            $table->dropForeign(['page_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['comment_id']);
            $table->dropForeign(['email_id']);
            $table->dropForeign(['attachment_id']);
        });
    }
};
