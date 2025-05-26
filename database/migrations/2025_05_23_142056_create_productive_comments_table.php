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
            $table->id();
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
            // Foreign keys without constraints - we'll add constraints in a separate migration
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('creator_id')->nullable();
            $table->foreignId('deal_id')->nullable();
            $table->foreignId('discussion_id')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->foreignId('person_id')->nullable();
            $table->foreignId('pinned_by_id')->nullable();
            $table->foreignId('task_id')->nullable();
            $table->foreignId('purchase_order_id')->nullable();
            $table->foreignId('attachment_id')->nullable();
            
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
