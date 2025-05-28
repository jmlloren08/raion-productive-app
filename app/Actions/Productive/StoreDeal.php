<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductivePerson;
use App\Models\ProductiveDocumentType;
use App\Models\ProductiveDealStatus;
use App\Models\ProductiveLostReason;
use App\Models\ProductiveContract;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveTaxRate;
use App\Models\ProductivePipeline;
use App\Models\ProductiveApa;
use App\Models\ProductivePeople;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreDeal extends AbstractAction
{
    /**
     * Required fields that must be present in the deal data
     */
    protected array $requiredFields = [
        'name',
        'date',
        'number'
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'creator_id' => ProductivePeople::class,
        'company_id' => ProductiveCompany::class,
        'document_type_id' => ProductiveDocumentType::class,
        'responsible_id' => ProductivePeople::class,
        'deal_status_id' => ProductiveDealStatus::class,
        'project_id' => ProductiveProject::class,
        'lost_reason_id' => ProductiveLostReason::class,
        'contract_id' => ProductiveContract::class,
        'contact_id' => ProductiveContactEntry::class,
        'subsidiary_id' => ProductiveSubsidiary::class,
        'tax_rate_id' => ProductiveTaxRate::class,
        'pipeline_id' => ProductivePipeline::class,
        'apa_id' => ProductiveApa::class
    ];

    /**
     * Store a deal in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $dealData = $parameters['dealData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$dealData) {
            throw new \Exception('Deal data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing deal: {$dealData['id']}");
            }

            // Validate basic data structure
            if (!isset($dealData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $dealData['attributes'] ?? [];
            $relationships = $dealData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($dealData['type'])) {
                $attributes['type'] = $dealData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $dealData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $dealData['id'],
                'type' => $attributes['type'] ?? $dealData['type'] ?? 'deals',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Deal', $command);

            // Debug log final data
            if ($command instanceof Command) {
                $command->info("Final deal data structure prepared");
            }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update deal
            ProductiveDeal::updateOrCreate(
                ['id' => $dealData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored deal: {$attributes['name']} (ID: {$dealData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store deal {$dealData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store deal {$dealData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $dealId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $dealId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for deal {$dealId}: " . implode(', ', $missingFields);
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
            'tag_list',
            'custom_fields',
            'editor_config',
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
     * @param string $dealName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $dealName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'creator' => 'creator_id',
            'company' => 'company_id',
            'document_type' => 'document_type_id',
            'responsible' => 'responsible_id',
            'deal_status' => 'deal_status_id',
            'project' => 'project_id',
            'lost_reason' => 'lost_reason_id',
            'contract' => 'contract_id',
            'contact' => 'contact_id',
            'subsidiary' => 'subsidiary_id',
            'tax_rate' => 'tax_rate_id',
            'pipeline' => 'pipeline_id',
            'approval_policy_assignment' => 'apa_id'
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
                        $command->warn("Deal '{$dealName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'date' => 'required|date',
            'number' => 'required|string',
            'end_date' => 'nullable|date',
            'time_approval' => 'boolean',
            'expense_approval' => 'boolean',
            'client_access' => 'boolean',
            'deal_type_id' => 'integer',
            'budget' => 'boolean',
            'sales_status_updated_at' => 'nullable|date',
            'tag_list' => 'nullable|json',
            'origin_deal_id' => 'nullable|integer',
            'email_key' => 'nullable|string',
            'purchase_order_number' => 'nullable|string',
            'custom_fields' => 'nullable|json',
            'editor_config' => 'nullable|json',
            'tracking_type_id' => 'integer',
            'currency' => 'required|string',
            'currency_default' => 'required|string',
            'currency_normalized' => 'required|string',
            'creator_id' => 'nullable|string',
            'company_id' => 'nullable|string',
            'document_type_id' => 'nullable|string',
            'responsible_id' => 'nullable|string',
            'deal_status_id' => 'nullable|string',
            'project_id' => 'nullable|string',
            'lost_reason_id' => 'nullable|string',
            'contract_id' => 'nullable|string',
            'contact_id' => 'nullable|string',
            'subsidiary_id' => 'nullable|string',
            'tax_rate_id' => 'nullable|string',
            'pipeline_id' => 'nullable|string',
            'apa_id' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
