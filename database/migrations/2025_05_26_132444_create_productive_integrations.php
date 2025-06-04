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
        Schema::create('productive_integrations', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('integrations'); // type of integration, e.g., 'crm', 'email', etc.
            // Core attributes
            $table->string('name')->nullable();
            $table->integer('integration_type_id')->nullable();
            $table->string('realm_id')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->string('request_token')->nullable();
            $table->text('request_uri')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->string('account_code')->nullable();
            $table->string('deactivated_at')->nullable();
            $table->json('options')->nullable();
            $table->boolean('export_number')->default(false);
            $table->boolean('export_attachment')->default(false);
            $table->boolean('export_expense_attachment')->nullable();
            $table->string('xero_organization_id')->nullable();
            $table->json('xero_organizations')->nullable();
            $table->boolean('use_expenses_in_xero')->default(false);
            $table->string('xero_default_expense_account_code')->nullable();
            $table->boolean('use_expense_sync')->nullable();
            $table->json('expense_account_code_mapping')->nullable();
            $table->boolean('payments_import')->default(false);
            $table->string('redirect_uri')->nullable();
            $table->json('calendars')->nullable();
            $table->string('exact_country')->nullable();
            $table->json('exact_divisions')->nullable();
            $table->string('exact_division')->nullable();
            $table->string('exact_division_id')->nullable();
            $table->string('xero_invoice_status_id')->nullable();
            $table->string('xero_expense_status_id')->nullable();
            $table->json('account_code_mapping')->nullable();
            $table->string('xero_reference')->nullable();
            $table->string('xero_internal_note_cf_id')->nullable();
            $table->json('item_mapping')->nullable();
            $table->string('quickbooks_memo')->nullable();
            $table->string('customer_memo_cf_id')->nullable();
            $table->string('default_item')->nullable();
            $table->string('calendar_write_status')->nullable();
            $table->json('calendar_write_options')->nullable();
            $table->string('google_events_write_scope')->nullable();
            $table->boolean('import_attachment')->nullable();
            $table->json('economic_product_mapping')->nullable();
            $table->string('default_product')->nullable();
            $table->json('slack_options')->nullable();
            $table->string('fortnox_default_account')->nullable();
            $table->json('fortnox_default_article')->nullable();
            $table->json('fortnox_article_mapping')->nullable();
            $table->json('fortnox_account_mapping')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('exact_ledger_manually')->nullable();
            $table->string('exact_default_ledger')->nullable();
            $table->json('exact_ledger_mapping')->nullable();
            $table->string('exact_default_journal')->nullable();
            $table->json('twinfield_offices')->nullable();
            $table->json('twinfield_invoice_destiny')->nullable();
            $table->string('twinfield_default_ledger')->nullable();
            $table->json('twinfield_ledger_mapping')->nullable();
            $table->string('twinfield_default_project')->nullable();
            $table->json('twinfield_project_mapping')->nullable();
            $table->string('twinfield_default_cost_center')->nullable();
            $table->json('twinfield_cost_center_mapping')->nullable();
            $table->string('hubspot_default_subsidiary_id')->nullable();
            $table->string('hubspot_default_deal_owner_id')->nullable();
            $table->string('hubspot_default_company_id')->nullable();
            $table->string('hubspot_default_template_id')->nullable();
            $table->json('hubspot_stages_mapping')->nullable();
            $table->boolean('hubspot_sync_deals')->nullable();
            $table->json('hubspot_pipelines')->nullable();
            $table->string('sage_default_ledger')->nullable();
            $table->json('sage_ledger_mapping')->nullable();
            $table->string('sage_country')->nullable();
            $table->string('sage_business_name')->nullable();
            $table->json('tax_rate_mapping')->nullable();
            // Relationships
            $table->unsignedBigInteger('subsidiary_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_integrations');
    }
};
