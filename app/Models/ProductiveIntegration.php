<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductiveIntegration extends Model
{
    protected $table = 'productive_integrations';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        // Base data
        'id',
        'type',
        // Attributes
        'name',
        'integration_type_id',
        'realm_id',
        'requested_at',
        'request_token',
        'request_uri',
        'connected_at',
        'account_code',
        'deactivated_at',
        'options',
        'export_number',
        'export_attachment',
        'export_expense_attachment',
        'xero_organization_id',
        'xero_organizations',
        'use_expenses_in_xero',
        'xero_default_expense_account_code',
        'use_expense_sync',
        'expense_account_code_mapping',
        'payments_import',
        'redirect_uri',
        'calendars',
        'exact_country',
        'exact_divisions',
        'exact_division',
        'exact_division_id',
        'xero_invoice_status_id',
        'xero_expense_status_id',
        'account_code_mapping',
        'xero_reference',
        'xero_internal_note_cf_id',
        'item_mapping',
        'quickbooks_memo',
        'customer_memo_cf_id',
        'default_item',
        'calendar_write_status',
        'calendar_write_options',
        'google_events_write_scope',
        'import_attachment',
        'economic_product_mapping',
        'default_product',
        'slack_options',
        'fortnox_default_account',
        'fortnox_default_article',
        'fortnox_article_mapping',
        'fortnox_account_mapping',
        'last_synced_at',
        'exact_ledger_manually',
        'exact_default_ledger',
        'exact_ledger_mapping',
        'exact_default_journal',
        'twinfield_offices',
        'twinfield_invoice_destiny',
        'twinfield_default_ledger',
        'twinfield_ledger_mapping',
        'twinfield_default_project',
        'twinfield_project_mapping',
        'twinfield_default_cost_center',
        'twinfield_cost_center_mapping',
        'hubspot_default_subsidiary_id',
        'hubspot_default_deal_owner_id',
        'hubspot_default_company_id',
        'hubspot_default_template_id',
        'hubspot_stages_mapping',
        'hubspot_sync_deals',
        'hubspot_pipelines',
        'sage_default_ledger',
        'sage_ledger_mapping',
        'sage_country',
        'sage_business_name',
        'tax_rate_mapping',
        // Relationships
        'subsidiary_id',
        'project_id',
        'creator_id',
        'deal_id',
    ];

    protected $casts = [
        'options' => 'array',
        'export_number' => 'boolean',
        'export_attachment' => 'boolean',
        'export_expense_attachment' => 'boolean',
        'use_expenses_in_xero' => 'boolean',
        'use_expense_sync' => 'boolean',
        'payments_import' => 'boolean',
        'calendars' => 'array',
        'exact_divisions' => 'array',
        'account_code_mapping' => 'array',
        'item_mapping' => 'array',
        'calendar_write_options' => 'array',
        'economic_product_mapping' => 'array',
        'fortnox_default_article' => 'array',
        'fortnox_article_mapping' => 'array',
        'fortnox_account_mapping' => 'array',
        'exact_ledger_manually' => 'boolean',
        'twinfield_offices' => 'array',
        'twinfield_invoice_destiny' => 'array',
        'twinfield_ledger_mapping' => 'array',
        'twinfield_project_mapping' => 'array',
        'twinfield_cost_center_mapping' => 'array',
    ];

    /**
     * Get the subsidiary associated with the integration.
     */
    public function subsidiary()
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'subsidiary_id');
    }

    /**
     * Get the project associated with the integration.
     */
    public function project()
    {
        return $this->belongsTo(ProductiveProject::class, 'project_id');
    }

    /**
     * Get the creator of the integration.
     */
    public function creator()
    {
        return $this->belongsTo(ProductivePeople::class, 'creator_id');
    }

    /**
     * Get the deal associated with the integration.
     */
    public function deal()
    {
        return $this->belongsTo(ProductiveDeal::class, 'deal_id');
    }
}
