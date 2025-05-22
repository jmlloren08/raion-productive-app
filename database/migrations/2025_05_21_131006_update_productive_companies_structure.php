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
            $table->json('contact')->nullable();
            $table->json('settings')->nullable();

            // Identifiers and codes
            $table->string('company_code')->nullable();
            $table->string('domain')->nullable();
            $table->boolean('projectless_budgets')->default(false);
            $table->string('leitweg_id')->nullable();
            $table->string('buyer_reference')->nullable();
            $table->string('peppol_id')->nullable();

            // Foreign-key style references
            $table->unsignedBigInteger('default_subsidiary_id')->nullable();
            $table->unsignedBigInteger('default_tax_rate_id')->nullable();
            $table->unsignedBigInteger('default_document_type_id')->nullable();

            $table->text('description')->nullable();
            $table->integer('due_days')->nullable();

            // Tags array
            $table->json('tag_list')->nullable(); // e.g., ["Mining"]

            // Metadata
            $table->boolean('sample_data')->default(false);
            $table->string('external_id')->nullable();
            $table->boolean('external_sync')->default(false);

            // Laravel's created_at and updated_at
            $table->timestamps();

            // Soft delete support
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
