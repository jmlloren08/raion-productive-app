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
            $table->string('content_type')->nullable();
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

            $table->string('creator_id')->nullable();
            $table->string('invoice_id')->nullable();
            $table->string('purchase_order_id')->nullable();
            $table->string('bill_id')->nullable();
            $table->string('email_id')->nullable();
            $table->string('page_id')->nullable();
            $table->string('expense_id')->nullable();
            $table->string('comment_id')->nullable();
            $table->string('task_id')->nullable();
            $table->string('document_style_id')->nullable();
            $table->string('document_type_id')->nullable();
            $table->string('deal_id')->nullable();
            
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
