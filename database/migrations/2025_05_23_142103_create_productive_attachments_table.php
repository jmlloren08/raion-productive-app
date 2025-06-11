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
            $table->unsignedBigInteger('id')->primary();
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

            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('email_id')->nullable();
            $table->unsignedBigInteger('page_id')->nullable();
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->unsignedBigInteger('comment_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('document_style_id')->nullable();
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();
            
            $table->timestamps();
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
