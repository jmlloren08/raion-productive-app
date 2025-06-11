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
        Schema::create('productive_subsidiaries', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('subsidiaries'); // type of subsidiary, e.g., 'project', 'task', etc();
            // Core attributes
            $table->string('name');
            $table->string('invoice_number_format')->nullable();
            $table->string('invoice_number_scope')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->boolean('show_delivery_date')->default(false);
            $table->string('einvoice_payment_means_type_id')->nullable();
            $table->string('einvoice_download_format_id')->nullable();
            $table->string('peppol_id')->nullable();
            $table->string('export_integration_type_id')->nullable();
            $table->string('invoice_logo_url')->nullable();
            // Relationships
            $table->unsignedBigInteger('contact_entry_id')->nullable();
            $table->unsignedBigInteger('custom_domain_id')->nullable();
            $table->unsignedBigInteger('default_tax_rate_id')->nullable();
            $table->unsignedBigInteger('integration_id')->nullable();

            $table->timestamps();
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
