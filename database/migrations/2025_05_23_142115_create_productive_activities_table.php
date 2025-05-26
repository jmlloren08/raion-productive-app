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
            $table->id();
            $table->string('type')->default('activities'); // type of activity, e.g., 'call', 'email', etc.
            // Core attributes
            $table->string('event')->nullable();
            $table->json('changeset')->nullable();
            $table->foreignId('item_id')->nullable();
            $table->string('item_type')->nullable();
            $table->string('item_name')->nullable();
            $table->timestamp('item_deleted_at')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('parent_type')->nullable();
            $table->string('parent_name')->nullable();
            $table->timestamp('parent_deleted_at')->nullable();
            $table->foreignId('root_id')->nullable();
            $table->string('root_type')->nullable();
            $table->string('root_name')->nullable();
            $table->timestamp('root_deleted_at')->nullable();
            $table->boolean('deal_is_budget')->default(false);
            $table->string('task_id')->nullable();
            $table->string('deal_id')->nullable();
            $table->foreignId('booking_id')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->foreignId('discussion_id')->nullable();
            $table->string('engagement_id')->nullable();
            $table->foreignId('page_id')->nullable();
            $table->foreignId('person_id')->nullable();
            $table->foreignId('purchase_order_id')->nullable();
            $table->boolean('made_by_automation')->default(false);
            // Foreign keys without constraints - we'll add constraints in a separate migration
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('creator_id')->nullable();
            $table->foreignId('comment_id')->nullable();
            $table->foreignId('email_id')->nullable();
            $table->foreignId('attachment_id')->nullable();
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
