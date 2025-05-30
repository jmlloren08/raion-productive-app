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
        Schema::create('productive_comments', function (Blueprint $table) {
            // Primary key
            $table->id('id')->primary();
            $table->string('type')->default('comments'); // type of comment, e.g., 'text', 'image', etc.
            // Core attributes
            $table->text('body');
            $table->string('commentable_type');
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('deleted_at_api')->nullable();
            $table->boolean('draft')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('hidden')->default(false);
            $table->timestamp('pinned_at')->nullable();
            $table->json('reactions')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->integer('version_number')->nullable();
            
            $table->string('company_id')->nullable();
            $table->string('creator_id')->nullable();
            $table->string('deal_id')->nullable();
            $table->string('discussion_id')->nullable();
            $table->string('invoice_id')->nullable();
            $table->string('person_id')->nullable();
            $table->string('pinned_by_id')->nullable();
            $table->string('task_id')->nullable();
            $table->string('purchase_order_id')->nullable();
            $table->string('attachment_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_comments');
    }
};
