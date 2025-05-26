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
        Schema::create('productive_services', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('services'); // type of service, e.g., 'email', 'chat', etc.
            // Core attributes
            $table->string('name');
            $table->integer('position')->default(0);
            $table->timestamp('deleted_at_api')->nullable();
            $table->boolean('billable')->default(true);
            $table->text('description')->nullable();
            $table->boolean('time_tracking_enabled')->default(true);
            $table->boolean('expense_tracking_enabled')->default(false);
            $table->boolean('booking_tracking_enabled')->default(true);
            $table->string('origin_service_id')->nullable();
            $table->integer('initial_service_id')->nullable();
            $table->boolean('budget_cap_enabled')->default(false);
            $table->json('editor_config')->nullable();
            $table->json('custom_fields')->nullable();
            $table->integer('pricing_type_id')->default(3);
            $table->integer('billing_type_id')->default(2);
            $table->integer('unapproved_time')->default(0);
            $table->integer('worked_time')->default(0);
            $table->integer('billable_time')->default(0);
            $table->integer('estimated_time')->default(0);
            $table->integer('budgeted_time')->default(0);
            $table->integer('rolled_over_time')->default(0);
            $table->integer('booked_time')->default(0);
            $table->integer('unit_id')->default(1);
            $table->integer('future_booked_time')->default(0);
            $table->decimal('markup', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('quantity', 10, 2)->default(0.00);
            $table->string('currency')->default('AUD');
            $table->string('currency_default')->default('AUD');
            $table->string('currency_normalized')->default('AUD');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('price_default', 10, 2)->default(0.00);
            $table->decimal('price_normalized', 10, 2)->default(0.00);
            $table->decimal('revenue', 10, 2)->default(0.00);
            $table->decimal('revenue_default', 10, 2)->default(0.00);
            $table->decimal('revenue_normalized', 10, 2)->default(0.00);
            $table->decimal('projected_revenue', 10, 2)->default(0.00);
            $table->decimal('projected_revenue_default', 10, 2)->default(0.00);
            $table->decimal('projected_revenue_normalized', 10, 2)->default(0.00);
            $table->decimal('expense_amount', 10, 2)->default(0.00);
            $table->decimal('expense_amount_default', 10, 2)->default(0.00);
            $table->decimal('expense_amount_normalized', 10, 2)->default(0.00);
            $table->decimal('expense_billable_amount', 10, 2)->default(0.00);
            $table->decimal('expense_billable_amount_default', 10, 2)->default(0.00);
            $table->decimal('expense_billable_amount_normalized', 10, 2)->default(0.00);
            $table->decimal('budget_total', 10, 2)->default(0.00);
            $table->decimal('budget_total_default', 10, 2)->default(0.00);
            $table->decimal('budget_total_normalized', 10, 2)->default(0.00);
            $table->decimal('budget_used', 10, 2)->default(0.00);
            $table->decimal('budget_used_default', 10, 2)->default(0.00);
            $table->decimal('budget_used_normalized', 10, 2)->default(0.00);
            $table->decimal('future_revenue', 10, 2)->default(0.00);
            $table->decimal('future_revenue_default', 10, 2)->default(0.00);
            $table->decimal('future_revenue_normalized', 10, 2)->default(0.00);
            $table->decimal('future_budget_used', 10, 2)->default(0.00);
            $table->decimal('future_budget_used_default', 10, 2)->default(0.00);
            $table->decimal('future_budget_used_normalized', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount_default', 10, 2)->default(0.00);
            $table->decimal('discount_amount_normalized', 10, 2)->default(0.00);
            $table->decimal('markup_amount', 10, 2)->default(0.00);
            $table->decimal('markup_amount_default', 10, 2)->default(0.00);
            $table->decimal('markup_amount_normalized', 10, 2)->default(0.00);
            // Relationships
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('service_type_id')->nullable();
            $table->foreignId('deal_id')->nullable();
            $table->foreignId('person_id')->nullable();
            $table->foreignId('section_id')->nullable();

            $table->json('custom_field_people')->nullable();
            $table->json('custom_field_attachments')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_services');
    }
};
