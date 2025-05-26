<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */    public function up(): void
    {
        Schema::create('productive_contact_entries', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type_name')->default('contact_entries'); // type of event, e.g., 'meeting', 'call', etc.
            // Core attributes
            $table->string('contactable_type')->nullable();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('country')->nullable();
            $table->string('vat')->nullable();
            $table->string('billing_address')->nullable();
            // Foreign keys without constraints - we'll add constraints in a separate migration
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('subsidiary_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_contact_entries');
    }
};
