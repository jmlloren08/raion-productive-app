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
            $table->string('id')->primary();
            $table->string('type')->default('deals'); // type of deal, e.g., 'deal', 'opportunity', etc.
            // Core attributes
            $table->string('name');
            $table->date('date');
            $table->date('end_date')->nullable();
            $table->string('number');
            $table->string('deal_number');
            $table->string('suffix')->nullable();
            $table->boolean('time_approval')->default(false);
            $table->boolean('expense_approval')->default(false);
            $table->boolean('client_access')->default(false);
            $table->integer('deal_type_id');
            $table->boolean('budget')->default(false);
            $table->timestamp('sales_status_updated_at')->nullable();
            $table->json('tag_list')->nullable();
            $table->integer('origin_deal_id')->nullable();
            $table->string('email_key');
            $table->string('purchase_order_number')->nullable();
            $table->json('custom_fields')->nullable();
            $table->integer('position')->nullable();
            $table->boolean('service_type_restricted_tracking')->default(false);
            $table->integer('tracking_type_id');
            $table->json('editor_config')->nullable();
            $table->decimal('discount', 8, 2)->nullable();
            $table->integer('man_day_minutes');
            $table->integer('rounding_interval_id');
            $table->integer('rounding_method_id');
            $table->boolean('validate_expense_when_closing');
            $table->integer('billable_time');
            $table->decimal('budget_warning', 8, 2)->nullable();
            $table->integer('estimated_time');
            $table->integer('budgeted_time');
            $table->integer('worked_time');
            $table->integer('time_to_close')->nullable();
            $table->integer('probability');
            $table->integer('previous_probability')->nullable();
            $table->text('note_interpolated')->nullable();
            $table->text('proposal_note_interpolated')->nullable();
            $table->integer('todo_count');
            $table->date('todo_due_date')->nullable();
            $table->text('note')->nullable();
            $table->text('proposal_note')->nullable();
            $table->text('lost_comment')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->date('delivered_on')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('deleted_at_api')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('sales_closed_at')->nullable();
            $table->decimal('exchange_rate', 8, 2);
            $table->date('exchange_date');
            $table->date('sales_closed_on')->nullable();
            $table->boolean('sample_data');
            $table->integer('external_id')->nullable();
            $table->boolean('external_sync');
            $table->integer('manual_invoicing_status_id');
            $table->string('currency');
            $table->string('currency_default');
            $table->string('currency_normalized');
            $table->decimal('revenue', 15, 2);
            $table->decimal('revenue_default', 15, 2);
            $table->decimal('revenue_normalized', 15, 2);
            $table->decimal('services_revenue', 15, 2);
            $table->decimal('services_revenue_default', 15, 2);
            $table->decimal('services_revenue_normalized', 15, 2);
            $table->decimal('budget_total', 15, 2);
            $table->decimal('budget_total_default', 15, 2);
            $table->decimal('budget_total_normalized', 15, 2);
            $table->decimal('budget_used', 15, 2);
            $table->decimal('budget_used_default', 15, 2);
            $table->decimal('budget_used_normalized', 15, 2);
            $table->decimal('projected_revenue', 15, 2);
            $table->decimal('projected_revenue_default', 15, 2);
            $table->decimal('projected_revenue_normalized', 15, 2);
            $table->decimal('invoiced', 15, 2);
            $table->decimal('invoiced_default', 15, 2);
            $table->decimal('invoiced_normalized', 15, 2);
            $table->decimal('pending_invoicing', 15, 2);
            $table->decimal('pending_invoicing_default', 15, 2);
            $table->decimal('pending_invoicing_normalized', 15, 2);
            $table->decimal('manually_invoiced', 15, 2);
            $table->decimal('manually_invoiced_default', 15, 2);
            $table->decimal('manually_invoiced_normalized', 15, 2);
            $table->decimal('draft_invoiced', 15, 2);
            $table->decimal('draft_invoiced_default', 15, 2);
            $table->decimal('draft_invoiced_normalized', 15, 2);
            $table->decimal('amount_credited', 15, 2);
            $table->decimal('amount_credited_default', 15, 2);
            $table->decimal('amount_credited_normalized', 15, 2);
            $table->decimal('expense', 15, 2);
            $table->decimal('expense_default', 15, 2);
            $table->decimal('expense_normalized', 15, 2);
            // Relationships
            $table->foreignId('creator_id')->nullable();
            $table->string('company_id')->nullable();
            $table->foreignId('document_type_id')->nullable();
            $table->json('proposal_document_type')->nullable();
            $table->foreignId('responsible_id')->nullable();
            $table->foreignId('deal_status_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('lost_reason_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('contact_id')->nullable();
            $table->foreignId('subsidiary_id')->nullable();
            $table->json('template')->nullable();
            $table->foreignId('tax_rate_id')->nullable();
            $table->foreignId('pipeline_id')->nullable();
            $table->json('origin_deal')->nullable();
            $table->foreignId('apa_id')->nullable();
            $table->json('next_todo')->nullable();

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
        Schema::dropIfExists('productive_deals');
    }
};
