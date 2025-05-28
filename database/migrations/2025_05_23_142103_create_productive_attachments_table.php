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
        Schema::create('productive_attachments', function (Blueprint $table) {
            // Primary key
            $table->id('id')->primary();
            $table->string('type')->default('attachments'); // type of attachment, e.g., 'file', 'image', etc.
            // Core attributes
            $table->string('name');
            $table->string('content_type');
            $table->integer('size');
            $table->text('url');
            $table->string('thumb')->nullable();
            $table->text('temp_url');
            $table->boolean('resized')->default(false);
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('deleted_at_api')->nullable();
            $table->string('attachment_type')->nullable();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('attachable_type');

            $table->foreignId('organization_id')->nullable();
            $table->foreignId('creator_id')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->foreignId('purchase_order_id')->nullable();
            $table->foreignId('bill_id')->nullable();
            $table->foreignId('email_id')->nullable();
            $table->foreignId('page_id')->nullable();
            $table->foreignId('expense_id')->nullable();
            $table->foreignId('comment_id')->nullable();
            $table->foreignId('task_id')->nullable();
            $table->foreignId('document_style_id')->nullable();
            $table->foreignId('document_type_id')->nullable();
            $table->string('deal_id')->nullable();
            // Relationships
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_attachments');
    }
};
