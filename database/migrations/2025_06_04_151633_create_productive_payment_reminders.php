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
        Schema::create('productive_payment_reminders', function (Blueprint $table) {
            
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('payment_reminders'); 

            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->text('subject')->nullable();
            $table->text('subject_parsed')->nullable();
            $table->json('to')->nullable();
            $table->json('from')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->text('body')->nullable();
            $table->text('body_parsed')->nullable();
            $table->dateTime('scheduled_on')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->dateTime('stopped_at')->nullable();
            $table->boolean('before_due_date')->default(false);
            $table->integer('reminder_period')->nullable();
            $table->unsignedBigInteger('reminder_stopped_reason_id')->nullable();
            
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('updater_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('prs_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_payment_reminders');
    }
};
