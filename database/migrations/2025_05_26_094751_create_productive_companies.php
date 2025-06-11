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
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('companies'); // type of company, e.g., 'company', 'customer', etc.
            // Core attributes
            $table->string('name');
            $table->string('billing_name')->nullable();
            $table->string('vat')->nullable();
            $table->string('default_currency')->nullable();
            $table->timestamp('created_at_api')->useCurrent();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('avatar_url')->nullable();
            $table->json('invoice_email_recipients')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('company_code')->nullable();
            $table->string('domain')->nullable();
            $table->boolean('projectless_budgets')->default(false);
            $table->string('leitweg_id')->nullable();
            $table->string('buyer_reference')->nullable();
            $table->string('peppol_id')->nullable();
            $table->integer('default_subsidiary_id')->nullable();
            $table->integer('default_tax_rate_id')->nullable();
            $table->integer('default_document_type_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('due_days')->nullable();
            $table->json('tag_list')->nullable();
            $table->json('contact')->nullable();
            $table->boolean('sample_data')->default(false);
            $table->json('settings')->nullable();
            $table->string('external_id')->nullable();
            $table->boolean('external_sync')->default(false);

            // Relationships
            $table->unsignedBigInteger('subsidiary_id')->nullable();
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            
            $table->json('custom_field_people')->nullable();
            $table->json('custom_field_attachments')->nullable();

            $table->timestamps();
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
