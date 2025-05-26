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
        Schema::create('productive_custom_domains', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('custom_domains'); // type of custom domain, e.g., 'organization', 'user', etc.
            // Core attributes
            $table->string('name');
            $table->timestamp('verified_at')->nullable();
            $table->string('email_sender_name')->nullable();
            $table->string('email_sender_address')->nullable();
            $table->string('mailgun_dkim')->nullable();
            $table->string('mailgun_spf')->nullable();
            $table->json('mailgun_mx')->nullable();
            $table->boolean('allow_user_email')->default(false);
            // Relationships
            $table->foreignId('organization_id')->nullable();

            $table->json('subsidiaries')->nullable(); // JSON to store subsidiary domains or related information
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_custom_domains');
    }
};
