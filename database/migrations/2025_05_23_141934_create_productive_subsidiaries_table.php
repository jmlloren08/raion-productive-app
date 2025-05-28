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
        Schema::create('productive_subsidiaries', function (Blueprint $table) {            // Primary key
            $table->string('id')->primary();
            $table->string('type')->default('subsidiaries'); // type of subsidiary, e.g., 'project', 'task', etc();
            // Core attributes
            // Core attributes
            $table->string('name');
            $table->string('invoice_number_format')->nullable();
            $table->string('invoice_number_scope')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->boolean('show_delivery_date')->default(false);
            $table->unsignedBigInteger('einvoice_payment_means_type_id')->nullable();
            $table->unsignedBigInteger('einvoice_download_format_id')->nullable();
            $table->string('peppol_id')->nullable();
            $table->unsignedBigInteger('export_integration_type_id')->nullable();
            $table->text('invoice_logo_url')->nullable();  // Allow NULL values since they can come from the API
            // Relationships
            $table->foreignId('bill_from_id')->nullable();
            $table->foreignId('custom_domain_id')->nullable();
            $table->foreignId('default_tax_rate_id')->nullable();
            $table->foreignId('integration_id')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_subsidiaries');
    }
};
