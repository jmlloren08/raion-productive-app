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
            $table->id();
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
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_reminder_sequence_id')->nullable();
            $table->unsignedBigInteger('thread_id')->nullable();
            $table->unsignedBigInteger('integration_id')->nullable();
            $table->json('email_recipients')->nullable();
            $table->json('recipients')->nullable();
            $table->json('to_recipients')->nullable()->constrained('productive_people')->nullOnDelete();
            $table->json('cc_recipients')->nullable()->constrained('productive_people')->nullOnDelete();
            $table->json('bcc_recipients')->nullable()->constrained('productive_people')->nullOnDelete();
            $table->foreignId('attachments')->nullable()->constrained('productive_attachments')->nullOnDelete();

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
