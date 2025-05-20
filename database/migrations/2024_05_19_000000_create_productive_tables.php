<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productive_companies', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('type')->default('companies');
            $table->string('name');
            $table->string('billing_name')->nullable();
            $table->string('vat')->nullable();
            $table->string('default_currency')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('avatar_url')->nullable();

            // Additional fields
            $table->json('invoice_email_recipients')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('company_code')->nullable();
            $table->string('domain')->nullable();
            $table->boolean('projectless_budgets')->default(false);
            $table->string('leitweg_id')->nullable();
            $table->string('buyer_reference')->nullable();
            $table->string('peppol_id')->nullable();
            $table->string('default_subsidiary_id')->nullable();
            $table->string('default_tax_rate_id')->nullable();
            $table->string('default_document_type_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('due_days')->nullable();
            $table->json('tag_list')->nullable();
            $table->json('contact')->nullable();
            $table->boolean('sample_data')->default(false);
            $table->json('settings')->nullable();
            $table->string('external_id')->nullable();
            $table->json('external_sync')->nullable();

            // Relationship data // Organization relationship
            $table->string('organization_type')->nullable();
            $table->string('organization_id')->nullable();

            // Other relationships metadata
            $table->boolean('default_subsidiary_meta_included')->nullable();
            $table->json('default_tax_rate_meta_included')->nullable();
            $table->json('custom_field_people_meta_included')->nullable();
            $table->json('custom_field_attachments_meta_included')->nullable();
        });        // Create the projects table
        Schema::create('productive_projects', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id')->nullable(); // Changed to nullable
            $table->string('type')->default('projects');
            $table->string('name');
            $table->string('number')->nullable();
            $table->json('preferences')->nullable();
            $table->string('project_number')->nullable();
            $table->string('project_type_id')->nullable();
            $table->string('project_color_id')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('public_access')->default(false);
            $table->boolean('time_on_tasks')->default(false);
            $table->json('tag_colors')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->boolean('template')->default(false);
            $table->json('custom_fields')->nullable();
            $table->json('task_custom_fields_ids')->nullable();
            $table->json('task_custom_fields_positions')->nullable();
            $table->boolean('sample_data')->default(false);

            // Relationships
            $table->string('organization_type')->nullable();
            $table->string('organization_id')->nullable();

            // Meta relationships stored as JSON
            $table->boolean('company_meta_included')->nullable();
            $table->boolean('project_manager_meta_included')->nullable();
            $table->boolean('last_actor_meta_included')->nullable();
            $table->boolean('workflow_meta_included')->nullable();
            $table->boolean('custom_field_people_meta_included')->nullable();
            $table->boolean('custom_field_attachments_meta_included')->nullable();
            $table->boolean('template_object_meta_included')->nullable();

            $table->foreign('company_id')
                ->references('id')
                ->on('productive_companies')
                ->onDelete('cascade');
        });        Schema::create('productive_deals', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id')->nullable(); // Changed to nullable
            $table->string('project_id')->nullable();

            // Basic attributes
            $table->string('type')->default('deals');
            $table->string('name');
            $table->date('date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('number')->nullable();
            $table->string('deal_number')->nullable();
            $table->string('suffix')->nullable();

            // Approval and access settings
            $table->boolean('time_approval')->default(false);
            $table->boolean('expense_approval')->default(false);
            $table->boolean('client_access')->default(false);

            // Deal type and status
            $table->string('deal_type_id')->nullable();
            $table->boolean('budget')->default(false);
            $table->timestamp('sales_status_updated_at')->nullable();
            $table->json('tag_list')->nullable();
            $table->string('original_deal_id')->nullable();

            // Financial attributes
            $table->decimal('profit_margin', 8, 2)->nullable();
            $table->string('email_key')->nullable();
            $table->string('purchase_order_number')->nullable();
            $table->json('custom_fields')->nullable();
            $table->integer('position')->nullable();

            // Tracking settings
            $table->boolean('service_type_restricted_tracking')->default(false);
            $table->string('tracking_type_id')->nullable();
            $table->json('editor_config')->nullable();

            // Financial calculations
            $table->decimal('discount', 8, 2)->nullable();
            $table->integer('man_day_minutes')->nullable();
            $table->string('rounding_interval_id')->nullable();
            $table->string('rounding_method_id')->nullable();
            $table->boolean('validate_expense_when_closing')->default(false);
            $table->integer('billable_time')->nullable();
            $table->string('budget_warning')->nullable();

            // Time tracking
            $table->decimal('estimated_time', 10, 2)->nullable();
            $table->decimal('budgeted_time', 10, 2)->nullable();
            $table->decimal('worked_time', 10, 2)->nullable();
            $table->decimal('time_to_close', 10, 2)->nullable();

            // Sales attributes
            $table->decimal('probability', 5, 2)->nullable();
            $table->decimal('previous_probability', 5, 2)->nullable();
            $table->text('note_interpolated')->nullable();
            $table->text('proposal_note_interpolated')->nullable();
            $table->integer('todo_count')->nullable();
            $table->date('todo_due_date')->nullable();
            $table->text('note')->nullable();
            $table->text('proposal_note')->nullable();
            $table->text('lost_comment')->nullable();

            // Timestamps
            $table->timestamp('closed_at')->nullable();
            $table->date('delivered_on')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('sales_closed_at')->nullable();
            // Exchange rates
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->date('exchange_date')->nullable();

            $table->date('sales_closed_on')->nullable();

            // Other attributes
            $table->boolean('sample_data')->default(false);
            $table->string('external_id')->nullable();
            $table->boolean('external_sync')->nullable();
            $table->string('manual_invoicing_status_id')->nullable();

            // Currency and financial amounts
            $table->string('currency')->nullable();
            $table->string('currency_default')->nullable();
            $table->string('currency_normalized')->nullable();

            // Revenue fields
            $table->decimal('revenue', 15, 2)->nullable();
            $table->decimal('revenue_default', 15, 2)->nullable();
            $table->decimal('revenue_normalized', 15, 2)->nullable();
            $table->decimal('services_revenue', 15, 2)->nullable();
            $table->decimal('services_revenue_default', 15, 2)->nullable();
            $table->decimal('services_revenue_normalized', 15, 2)->nullable();

            // Budget fields
            $table->decimal('budget_total', 15, 2)->nullable();
            $table->decimal('budget_total_default', 15, 2)->nullable();
            $table->decimal('budget_total_normalized', 15, 2)->nullable();
            $table->decimal('budget_used', 15, 2)->nullable();
            $table->decimal('budget_used_default', 15, 2)->nullable();
            $table->decimal('budget_used_normalized', 15, 2)->nullable();

            // Projected and actual financials
            $table->decimal('projected_revenue', 15, 2)->nullable();
            $table->decimal('projected_revenue_default', 15, 2)->nullable();
            $table->decimal('projected_revenue_normalized', 15, 2)->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->decimal('cost_default', 15, 2)->nullable();
            $table->decimal('cost_normalized', 15, 2)->nullable();
            $table->decimal('profit', 15, 2)->nullable();
            $table->decimal('profit_default', 15, 2)->nullable();
            $table->decimal('profit_normalized', 15, 2)->nullable();

            // Invoice related fields
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

            // Relationships
            $table->string('organization_type')->nullable();
            $table->string('organization_id')->nullable();

            // Meta relationships stored as JSON
            $table->boolean('creator_meta_included')->default(false);
            $table->boolean('company_meta_included')->default(false);
            $table->boolean('document_type_meta_included')->default(false);
            $table->boolean('proposal_document_type_meta_included')->default(false);
            $table->boolean('responsible_meta_included')->default(false);
            $table->boolean('deal_status_meta_included')->default(false);
            $table->boolean('project_meta_included')->default(false);
            $table->boolean('lost_reason_meta_included')->default(false);
            $table->boolean('contract_meta_included')->default(false);
            $table->boolean('contact_meta_included')->default(false);
            $table->boolean('subsidiary_meta_included')->default(false);
            $table->boolean('template_meta_included')->default(false);
            $table->boolean('tax_rate_meta_included')->default(false);
            $table->boolean('pipeline_meta_included')->default(false);
            $table->boolean('origin_deal_meta_included')->default(false);
            $table->boolean('approval_policy_assignment_meta_included')->default(false);
            $table->boolean('custom_field_people_meta_included')->default(false);
            $table->boolean('custom_field_attachments_meta_included')->default(false);

            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('productive_companies')
                ->onDelete('cascade');

            $table->foreign('project_id')
                ->references('id')
                ->on('productive_projects')
                ->onDelete('set null');

            $table->foreign('original_deal_id')
                ->references('id')
                ->on('productive_deals')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productive_deals');
        Schema::dropIfExists('productive_projects');
        Schema::dropIfExists('productive_companies');
    }
};
