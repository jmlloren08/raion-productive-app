<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductiveTaxRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreCompany extends AbstractAction
{
    /**
     * Required fields that must be present in the company data
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'default_subsidiary_id' => ProductiveCompany::class,
        'default_tax_rate_id' => ProductiveTaxRate::class,
    ];

    /**
     * Store a company in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $companyData = $parameters['companyData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$companyData) {
            throw new \Exception('Company data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing company: {$companyData['id']}");
            }

            // Validate basic data structure
            if (!isset($companyData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $companyData['attributes'] ?? [];
            $relationships = $companyData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($companyData['type'])) {
                $attributes['type'] = $companyData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $companyData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $companyData['id'],
                'type' => $attributes['type'] ?? $companyData['type'] ?? 'companies',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Company', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update company
            ProductiveCompany::updateOrCreate(
                ['id' => $companyData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored company: {$attributes['name']} (ID: {$companyData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store company {$companyData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store company {$companyData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $companyId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $companyId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for company {$companyId}: " . implode(', ', $missingFields);
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
            'invoice_email_recipients',
            'custom_fields',
            'tag_list',
            'contact',
            'settings',
            'custom_field_people',
            'custom_field_attachments'
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
     * @param string $companyName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $companyName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'default_subsidiary' => 'default_subsidiary_id',
            'default_tax_rate' => 'default_tax_rate_id',
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
                        $command->warn("Company '{$companyName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'name' => 'required|string',
            'billing_name' => 'nullable|string',
            'vat' => 'nullable|string',
            'default_currency' => 'nullable|string',
            'created_at_api' => 'nullable|date',
            'last_activity_at' => 'nullable|date',
            'archived_at' => 'nullable|date',
            'avatar_url' => 'nullable|string',
            'invoice_email_recipients' => 'nullable|json',
            'custom_fields' => 'nullable|json',
            'company_code' => 'nullable|string',
            'domain' => 'nullable|string',
            'projectless_budgets' => 'boolean',
            'leitweg_id' => 'nullable|string',
            'buyer_reference' => 'nullable|string',
            'peppol_id' => 'nullable|string',
            'default_subsidiary_id' => 'nullable|string',
            'default_tax_rate_id' => 'nullable|string',
            'default_document_type_id' => 'nullable|string',
            'description' => 'nullable|string',
            'due_days' => 'nullable|integer',
            'tag_list' => 'nullable|json',
            'contact' => 'nullable|json',
            'sample_data' => 'boolean',
            'settings' => 'nullable|json',
            'external_id' => 'nullable|string',
            'external_sync' => 'boolean',
            'custom_field_people' => 'nullable|json',
            'custom_field_attachments' => 'nullable|json'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
