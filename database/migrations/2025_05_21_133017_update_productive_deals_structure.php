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
        Schema::create('productive_deals', function (Blueprint $table) {

            // Primary key
            $table->id();

            // Foreign keys (nullable to support partial data fetches)
            $table->foreignId('company_id')->nullable()->constrained('productive_companies')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('productive_projects')->nullOnDelete();

            $table->string('type')->default('deals'); // type of deal, e.g., 'deal', 'opportunity', etc.

            // Identification
            $table->string('name');
            $table->string('number')->nullable();
            $table->string('deal_number')->nullable();
            $table->string('suffix')->nullable();
            $table->string('email_key')->nullable();
            $table->string('position')->nullable();

            // Approval flags
            $table->boolean('time_approval')->default(false);
            $table->boolean('expense_approval')->default(false);
            $table->boolean('client_access')->default(false);
            $table->boolean('budget')->default(false);
            $table->boolean('service_type_restricted_tracking')->default(false);
            $table->boolean('validate_expense_when_closing')->default(false);
            $table->boolean('sample_data')->default(false);

            $table->boolean('external_sync')->default(false);
            $table->string('external_id')->nullable();

            // Status and dates
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('created_at_api')->nullable(); // avoid clashing with Laravel's timestamps
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('sales_status_updated_at')->nullable();
            $table->timestamp('sales_closed_at')->nullable();
            $table->date('sales_closed_on')->nullable();
            $table->date('delivered_on')->nullable();
            $table->date('exchange_date')->nullable();
            $table->date('date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('deleted_at')->nullable(); // soft delete support

            // Financials
            $table->decimal('revenue', 15, 2)->nullable();
            $table->decimal('revenue_default', 15, 2)->nullable();
            $table->decimal('revenue_normalized', 15, 2)->nullable();
            $table->decimal('services_revenue', 15, 2)->nullable();
            $table->decimal('services_revenue_default', 15, 2)->nullable();
            $table->decimal('services_revenue_normalized', 15, 2)->nullable();
            $table->decimal('budget_total', 15, 2)->nullable();
            $table->decimal('budget_total_default', 15, 2)->nullable();
            $table->decimal('budget_total_normalized', 15, 2)->nullable();
            $table->decimal('budget_used', 15, 2)->nullable();
            $table->decimal('budget_used_default', 15, 2)->nullable();
            $table->decimal('budget_used_normalized', 15, 2)->nullable();
            $table->decimal('projected_revenue', 15, 2)->nullable();
            $table->decimal('projected_revenue_default', 15, 2)->nullable();
            $table->decimal('projected_revenue_normalized', 15, 2)->nullable();
            $table->decimal('invoiced', 15, 2)->nullable();
            $table->decimal('invoiced_default', 15, 2)->nullable();
            $table->decimal('invoiced_normalized', 15, 2)->nullable();
            $table->decimal('pending_invoicing', 15, 2)->nullable();
            $table->decimal('pending_invoicing_default', 15, 2)->nullable();
            $table->decimal('pending_invoicing_normalized', 15, 2)->nullable();
            $table->decimal('manually_invoiced', 15, 2)->nullable();
            $table->decimal('manually_invoiced_default', 15, 2)->nullable();
            $table->decimal('manually_invoiced_normalized', 15, 2)->nullable();
            $table->decimal('draft_invoiced', 15, 2)->nullable();
            $table->decimal('draft_invoiced_default', 15, 2)->nullable();
            $table->decimal('draft_invoiced_normalized', 15, 2)->nullable();
            $table->decimal('amount_credited', 15, 2)->nullable();
            $table->decimal('amount_credited_default', 15, 2)->nullable();
            $table->decimal('amount_credited_normalized', 15, 2)->nullable();
            $table->decimal('expense', 15, 2)->nullable();
            $table->decimal('expense_default', 15, 2)->nullable();
            $table->decimal('expense_normalized', 15, 2)->nullable();
            $table->decimal('discount', 15, 2)->nullable();

            // Tracking & budgeting
            $table->unsignedInteger('man_day_minutes')->nullable();
            $table->unsignedInteger('billable_time')->nullable();
            $table->unsignedInteger('estimated_time')->nullable();
            $table->unsignedInteger('budgeted_time')->nullable();
            $table->unsignedInteger('worked_time')->nullable();
            $table->unsignedInteger('time_to_close')->nullable();
            $table->unsignedTinyInteger('budget_warning')->nullable();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->unsignedTinyInteger('previous_probability')->nullable();
            $table->unsignedInteger('todo_count')->default(0);
            $table->date('todo_due_date')->nullable();

            // Config IDs
            $table->unsignedBigInteger('deal_type_id')->nullable();
            $table->unsignedBigInteger('tracking_type_id')->nullable();
            $table->unsignedBigInteger('rounding_interval_id')->nullable();
            $table->unsignedBigInteger('rounding_method_id')->nullable();
            $table->unsignedBigInteger('manual_invoicing_status_id')->nullable();

            // Custom fields
            $table->json('custom_fields')->nullable();
            $table->json('editor_config')->nullable();

            // Notes
            $table->text('note')->nullable();
            $table->text('proposal_note')->nullable();
            $table->text('note_interpolated')->nullable();
            $table->text('proposal_note_interpolated')->nullable();
            $table->text('lost_comment')->nullable();

            // Purchase and exchange
            $table->string('purchase_order_number')->nullable();
            $table->string('exchange_rate')->nullable(); // store as string for precision

            // Currencies
            $table->string('currency')->nullable();
            $table->string('currency_default')->nullable();
            $table->string('currency_normalized')->nullable();

            $table->timestamps(); // Laravel created_at / updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_deals');
    }
};
