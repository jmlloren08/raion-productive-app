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
        Schema::create('productive_activities', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('activities'); // type of activity, e.g., 'call', 'email', etc.
            // Core attributes
            $table->string('event')->nullable();
            $table->json('changeset')->nullable();
            $table->string('item_id')->nullable();
            $table->string('item_type')->nullable();
            $table->string('item_name')->nullable();
            $table->timestamp('item_deleted_at')->nullable();
            $table->string('parent_id')->nullable();
            $table->string('parent_type')->nullable();
            $table->string('parent_name')->nullable();
            $table->timestamp('parent_deleted_at')->nullable();
            $table->string('root_id')->nullable();
            $table->string('root_type')->nullable();
            $table->string('root_name')->nullable();
            $table->timestamp('root_deleted_at')->nullable();
            $table->boolean('deal_is_budget')->default(false);
            $table->string('task_id')->nullable();
            $table->string('deal_id')->nullable();
            $table->string('booking_id')->nullable();
            $table->string('invoice_id')->nullable();
            $table->string('company_id')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->string('discussion_id')->nullable();
            $table->string('engagement_id')->nullable();
            $table->string('page_id')->nullable();
            $table->string('person_id')->nullable();
            $table->string('purchase_order_id')->nullable();
            $table->boolean('made_by_automation')->default(false);
            // Relationships
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('comment_id')->nullable();
            $table->unsignedBigInteger('email_id')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            // Arrays
            $table->json('roles')->nullable(); // e.g., ["admin", "user"]
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_activities');
    }
};
