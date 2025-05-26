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
        Schema::create('productive_companies', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('companies'); // type of company, e.g., 'company', 'customer', etc.
            // Core attributes
            $table->string('name');
            $table->string('billing_name')->nullable();
            $table->string('vat')->nullable();
            $table->string('default_currency', 10)->nullable();
            // Timestamps from API
            $table->timestamp('created_at_api')->nullable(); // renamed to prevent conflict with Laravel's own timestamps
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('avatar_url')->nullable();
            // JSON fields for nested/dynamic content
            $table->json('invoice_email_recipients')->nullable();
            $table->json('custom_fields')->nullable();
            // Identifiers and codes
            $table->string('company_code')->nullable();
            $table->string('domain')->nullable();
            $table->boolean('projectless_budgets')->default(false);
            $table->string('leitweg_id')->nullable();
            $table->string('buyer_reference')->nullable();
            $table->string('peppol_id')->nullable();
            // Relationships
            $table->foreignId('default_subsidiary_id')->nullable();
            $table->foreignId('default_tax_rate_id')->nullable();
            $table->foreignId('default_document_type_id')->nullable();
            // Other attributes
            $table->text('description')->nullable();
            $table->integer('due_days')->nullable();
            $table->json('tag_list')->nullable();
            $table->json('contact')->nullable();
            $table->boolean('sample_data')->default(false);
            $table->json('settings')->nullable();
            $table->string('external_id')->nullable();
            $table->boolean('external_sync')->default(false);
            // Foreign-key style references
            $table->foreignId('organization_id')->nullable();
            
            $table->json('custom_field_people')->nullable();
            $table->json('custom_field_attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_companies');
    }
};
