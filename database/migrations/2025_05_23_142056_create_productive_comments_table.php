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
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('comments'); // type of comment, e.g., 'text', 'image', etc.
            // Core attributes
            $table->text('body')->nullable();
            $table->string('commentable_type')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('deleted_at_api')->nullable();
            $table->boolean('draft')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('hidden')->default(false);
            $table->timestamp('pinned_at')->nullable();
            $table->json('reactions')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->integer('version_number')->nullable();
            
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->unsignedBigInteger('discussion_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('pinned_by_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            
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
