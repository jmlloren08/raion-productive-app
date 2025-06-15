<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveService;
use App\Models\ProductiveServiceType;
use App\Models\ProductiveDeal;
use App\Models\ProductiveSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreService extends AbstractAction
{
    /**
     * Required fields for a service
     * 
     * @var array
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     * 
     * @var array
     */
    protected array $foreignKeys = [
        'service_type_id' => ProductiveServiceType::class,
        'deal_id' => ProductiveDeal::class,
        'person_id' => ProductivePeople::class,
        'section_id' => ProductiveSection::class,
    ];

    /**
     * Execute the action to store a service from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "services",
     *     "attributes": {
     *         ...
     *     }
     * }
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $serviceData = $parameters['serviceData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$serviceData) {
            throw new \Exception('Service data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing service: {$serviceData['id']}");
            }

            // Validate basic data structure
            if (!isset($serviceData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $serviceData['attributes'] ?? [];
            $relationships = $serviceData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($serviceData['type'])) {
                $attributes['type'] = $serviceData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $serviceData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $serviceData['id'],
                'type' => $attributes['type'] ?? $serviceData['type'] ?? 'services',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Service', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update service
            ProductiveService::updateOrCreate(
                ['service_id' => $serviceData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored service {$attributes['name']} (ID: {$serviceData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store service {$serviceData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store service {$serviceData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $serviceId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $serviceId, ?Command $command): void
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
            $message = "Required fields missing for service {$serviceId}: " . implode(', ', $missingFields);
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
            'editor_config',
            'custom_fields',
            'custom_field_people',
            'custom_field_attachments',
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
     * @param string $serviceName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $serviceName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'service_type' => ['dbKey' => 'service_type_id', 'lookupColumn' => 'service_type_id'],
            'deal' => ['dbKey' => 'deal_id', 'lookupColumn' => 'deal_id'],
            'person' => ['dbKey' => 'person_id', 'lookupColumn' => 'person_id'],
            'section' => ['dbKey' => 'section_id', 'lookupColumn' => 'section_id'],
        ];

        foreach ($relationshipMap as $apiKey => $config) {
            if (isset($relationships[$apiKey]['data']['id'])) {
                $id = $relationships[$apiKey]['data']['id'];
                if ($command) {
                    $command->info("Processing relationship {$apiKey} with ID: {$id}");
                }

                // Get the model class for this relationship
                $modelClass = $this->foreignKeys[$config['dbKey']];

                if (!$modelClass::where($config['lookupColumn'], $id)->exists()) {
                    if ($command) {
                        $command->warn("Service '{$serviceName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
                    }
                    $data[$config['dbKey']] = null;
                } else {
                    $data[$config['dbKey']] = $id;
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
            'name' => 'string|required',
            'position' => 'integer|nullable',
            'deleted_at_api' => 'nullable|date',
            'billable' => 'boolean|nullable',
            'description' => 'nullable|string',
            'time_tracking_enabled' => 'boolean|nullable',
            'expense_tracking_enabled' => 'boolean|nullable',
            'booking_tracking_enabled' => 'boolean|nullable',
            'origin_service_id' => 'nullable|integer',
            'initial_service_id' => 'nullable|integer',
            'budget_cap_enabled' => 'boolean|nullable',
            'editor_config' => 'nullable|json',
            'custom_fields' => 'nullable|json',
            'pricing_type_id' => 'integer|nullable',
            'billing_type_id' => 'integer|nullable',
            'unapproved_time' => 'integer|nullable',
            'worked_time' => 'integer|nullable',
            'billable_time' => 'integer|nullable',
            'estimated_time' => 'integer|nullable',
            'budgeted_time' => 'integer|nullable',
            'rolled_over_time' => 'integer|nullable',
            'booked_time' => 'integer|nullable',
            'unit_id' => 'integer|nullable',
            'future_booked_time' => 'integer|nullable',
            'markup' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'quantity' => 'numeric|nullable',
            'currency' => 'string|nullable',
            'currency_default' => 'string|nullable',
            'currency_normalized' => 'string|nullable',
            'price' => 'numeric|nullable',
            'price_default' => 'numeric|nullable',
            'price_normalized' => 'numeric|nullable',
            'revenue' => 'numeric|nullable',
            'revenue_default' => 'numeric|nullable',
            'revenue_normalized' => 'numeric|nullable',
            'projected_revenue' => 'numeric|nullable',
            'projected_revenue_default' => 'numeric|nullable',
            'projected_revenue_normalized' => 'numeric|nullable',
            'expense_amount' => 'numeric|nullable',
            'expense_amount_default' => 'numeric|nullable',
            'expense_amount_normalized' => 'numeric|nullable',
            'expense_billable_amount' => 'numeric|nullable',
            'expense_billable_amount_default' => 'numeric|nullable',
            'expense_billable_amount_normalized' => 'numeric|nullable',
            'budget_total' => 'numeric|nullable',
            'budget_total_default' => 'numeric|nullable',
            'budget_total_normalized' => 'numeric|nullable',
            'budget_used' => 'numeric|nullable',
            'budget_used_default' => 'numeric|nullable',
            'budget_used_normalized' => 'numeric|nullable',
            'future_revenue' => 'numeric|nullable',
            'future_revenue_default' => 'numeric|nullable',
            'future_revenue_normalized' => 'numeric|nullable',
            'future_budget_used' => 'numeric|nullable',
            'future_budget_used_default' => 'numeric|nullable',
            'future_budget_used_normalized' => 'numeric|nullable',
            'discount_amount' => 'numeric|nullable',
            'discount_amount_default' => 'numeric|nullable',
            'discount_amount_normalized' => 'numeric|nullable',
            'markup_amount' => 'numeric|nullable',
            'markup_amount_default' => 'numeric|nullable',
            'markup_amount_normalized' => 'numeric|nullable',
            // Foreign keys
            'service_type_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'person_id' => 'nullable|string',
            'section_id' => 'nullable|string',

            'custom_field_people' => 'nullable|json',
            'custom_field_attachments' => 'nullable|json',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
