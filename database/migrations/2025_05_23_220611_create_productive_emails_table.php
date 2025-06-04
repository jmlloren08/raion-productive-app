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
        Schema::create('productive_emails', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('emails'); // type of email, e.g., 'inbox', 'sent', etc.
            // Core attributes
            $table->string('subject');
            $table->text('body')->nullable();
            $table->text('body_truncated')->nullable();
            $table->boolean('auto_linked')->default(false);
            $table->string('linked_type')->nullable();
            $table->integer('linked_id')->nullable();
            $table->string('external_id')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->boolean('outgoing')->nullable();
            $table->string('from')->nullable();
            // Relationships
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('prs_id')->nullable();

            $table->json('thread')->nullable();
            $table->json('integration')->nullable();
            $table->json('email_recipients')->nullable();
            $table->json('recipients')->nullable();
            $table->json('to_recipients')->nullable();
            $table->json('cc_recipients')->nullable();
            $table->json('bcc_recipients')->nullable();

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
        Schema::dropIfExists('productive_emails');
    }
};
