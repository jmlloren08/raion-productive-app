<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveDeal;
use App\Models\ProductiveIntegration;
use App\Models\ProductivePeople;
use App\Models\ProductiveProject;
use App\Models\ProductiveSubsidiary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreIntegration extends AbstractAction
{
    /**
     * Required fields that must be present in the integration data
     */
    protected array $requiredFields = [
        // No required fields defined by default
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'subsidiary_id' => ProductiveSubsidiary::class,
        'project_id' => ProductiveProject::class,
        'creator_id' => ProductivePeople::class,
        'deal_id' => ProductiveDeal::class,
    ];

    /**
     * Store an integration in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $integrationData = $parameters['integrationData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$integrationData) {
            throw new \Exception('Integration data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing integration: {$integrationData['id']}");
            }

            // Validate basic data structure
            if (!isset($integrationData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $integrationData['attributes'] ?? [];
            $relationships = $integrationData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($integrationData['type'])) {
                $attributes['type'] = $integrationData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $integrationData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $integrationData['id'],
                'type' => $attributes['type'] ?? $integrationData['type'] ?? 'integrations',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Integration', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update integration
            ProductiveIntegration::updateOrCreate(
                ['id' => $integrationData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored integration: {$attributes['name']} (ID: {$integrationData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store integration {$integrationData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store integration {$integrationData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $integrationId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $integrationId, ?Command $command): void
    {
        // Skip validation if no required fields are defined
        if (empty($this->requiredFields)) {
            return;
        }
        // Check for missing required fields
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for integration {$integrationId}: " . implode(', ', $missingFields);
            if ($command) {
                $command->error($message);
            }
            throw new \Exception($message);
        }
    }

    /**
     * Handle JSON fields in the data
     *
     * @param array &$data
     */
    protected function handleJsonFields(array &$data): void
    {
        $jsonFields = [
            'options',
            'xero_organizations',
            'expense_account_code_mapping',
            'calendars',
            'exact_divisions',
            'account_code_mapping',
            'item_mapping',
            'calendar_write_options',
            'economic_product_mapping',
            'slack_options',
            'fortnox_default_article',
            'fortnox_article_mapping',
            'fortnox_account_mapping',
            'exact_ledger_manually',
            'exact_ledger_mapping',
            'twinfield_offices',
            'twinfield_invoice_destiny',
            'twinfield_ledger_mapping',
            'twinfield_project_mapping',
            'twinfield_cost_center_mapping',
            'hubspot_stages_mapping',
            'hubspot_pipelines',
            'sage_ledger_mapping',
            'tax_rate_mapping',
        ];

        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                if (is_array($data[$field])) {
                    $data[$field] = json_encode($data[$field]);
                }
            }
        }
    }

    /**
     * Handle foreign key relationships
     *
     * @param array $relationships
     * @param array &$data
     * @param string $integrationName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $integrationName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'subsidiary' => 'subsidiary_id',
            'project' => 'project_id',
            'creator' => 'creator_id',
            'deal' => 'deal_id',
        ];

        foreach ($relationshipMap as $apiKey => $dbKey) {
            if (isset($relationships[$apiKey]['data']['id'])) {
                $id = $relationships[$apiKey]['data']['id'];
                if ($command) {
                    $command->info("Processing relationship {$apiKey} with ID: {$id}");
                }

                // Get the model class for this relationship
                $modelClass = $this->foreignKeys[$dbKey];

                if (!$modelClass::where('id', $id)->exists()) {
                    if ($command) {
                        $command->warn("Integration '{$integrationName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
                    }
                    $data[$dbKey] = null;
                } else {
                    $data[$dbKey] = $id;
                    if ($command) {
                        $command->info("Successfully linked {$apiKey} with ID: {$id}");
                    }
                }
            } else {
                if ($command) {
                    $command->info("No relationship data found for {$apiKey}");
                }
            }
        }
    }

    /**
     * Validate data types for all fields
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateDataTypes(array $data): void
    {
        $rules = [
            'name' => 'nullable|string',
            'integration_type_id' => 'nullable|integer',
            'realm_id' => 'nullable|string',
            'requested_at' => 'nullable|date',
            'request_token' => 'nullable|string',
            'request_uri' => 'nullable|string',
            'connected_at' => 'nullable|date',
            'account_code' => 'nullable|string',
            'deactivated_at' => 'nullable|string',
            'options' => 'nullable|json',
            'export_number' => 'boolean',
            'export_attachment' => 'boolean',
            'export_expense_attachment' => 'nullable|boolean',
            'xero_organization_id' => 'nullable|string',
            'xero_organizations' => 'nullable|json',
            'use_expenses_in_xero' => 'boolean',
            'xero_default_expense_account_code' => 'nullable|string',
            'use_expense_sync' => 'nullable|boolean',
            'expense_account_code_mapping' => 'nullable|json',
            'payments_import' => 'boolean',
            'redirect_uri' => 'nullable|string',
            'calendars' => 'nullable|json',
            'exact_country' => 'nullable|string',
            'exact_divisions' => 'nullable|json',
            'exact_division' => 'nullable|string',
            'exact_division_id' => 'nullable|string',
            'xero_invoice_status_id' => 'nullable|string',
            'xero_expense_status_id' => 'nullable|string',
            'account_code_mapping' => 'nullable|json',
            'xero_reference' => 'nullable|string',
            'xero_internal_note_cf_id' => 'nullable|string',
            'item_mapping' => 'nullable|json',
            'quickbooks_memo' => 'nullable|string',
            'customer_memo_cf_id' => 'nullable|string',
            'default_item' => 'nullable|string',
            'calendar_write_status' => 'nullable|string',
            'calendar_write_options' => 'nullable|json',
            'google_events_write_scope' => 'nullable|string',
            'import_attachment' => 'nullable|boolean',
            'economic_product_mapping' => 'nullable|json',
            'default_product' => 'nullable|string',
            'slack_options' => 'nullable|json',
            'fortnox_default_account' => 'nullable|string',
            'fortnox_default_article' => 'nullable|json',
            'fortnox_article_mapping' => 'nullable|json',
            'fortnox_account_mapping' => 'nullable|json',
            'last_synced_at' => 'nullable|date',
            'exact_ledger_manually' => 'nullable|json',
            'exact_default_ledger' => 'nullable|string',
            'exact_ledger_mapping' => 'nullable|json',
            'exact_default_journal' => 'nullable|string',
            'twinfield_offices' => 'nullable|json',
            'twinfield_invoice_destiny' => 'nullable|json',
            'twinfield_default_ledger' => 'nullable|string',
            'twinfield_ledger_mapping' => 'nullable|json',
            'twinfield_default_project' => 'nullable|string',
            'twinfield_project_mapping' => 'nullable|json',
            'twinfield_default_cost_center' => 'nullable|string',
            'twinfield_cost_center_mapping' => 'nullable|json',
            'hubspot_default_subsidiary_id' => 'nullable|string',
            'hubspot_default_deal_owner_id' => 'nullable|string',
            'hubspot_default_company_id' => 'nullable|string',
            'hubspot_default_template_id' => 'nullable|string',
            'hubspot_stages_mapping' => 'nullable|json',
            'hubspot_sync_deals' => 'nullable|boolean',
            'hubspot_pipelines' => 'nullable|json',
            'sage_default_ledger' => 'nullable|string',
            'sage_ledger_mapping' => 'nullable|json',
            'sage_country' => 'nullable|string',
            'sage_business_name' => 'nullable|string',
            'tax_rate_mapping' => 'nullable|json',
            // Relationships
            'subsidiary_id' => 'nullable|string',
            'project_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 