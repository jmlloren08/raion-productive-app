<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveServiceType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreServiceType extends AbstractAction
{
    /**
     * Required fields that must be present in the service type data
     */
    protected array $requiredFields = [
        // No required fields defined yet, can be added as needed
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'assignee_id' => ProductivePeople::class,
    ];

    /**
     * Store a service type in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $serviceTypeData = $parameters['serviceTypeData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$serviceTypeData) {
            throw new \Exception('Service type data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing service type: {$serviceTypeData['id']}");
            }

            // Validate basic data structure
            if (!isset($serviceTypeData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $serviceTypeData['attributes'] ?? [];
            $relationships = $serviceTypeData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($serviceTypeData['type'])) {
                $attributes['type'] = $serviceTypeData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $serviceTypeData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $serviceTypeData['id'],
                'type' => $attributes['type'] ?? $serviceTypeData['type'] ?? 'service_types',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Service Type', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update service type
            ProductiveServiceType::updateOrCreate(
                ['id' => $serviceTypeData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored service type: {$attributes['name']} (ID: {$serviceTypeData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store service type {$serviceTypeData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store service type {$serviceTypeData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $serviceTypeId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $serviceTypeId, ?Command $command): void
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
            $message = "Required fields missing for service type {$serviceTypeId}: " . implode(', ', $missingFields);
            if ($command) {
                $command->error($message);
            }
            throw new \Exception($message);
        }
    }

    /**
     * Handle foreign key relationships
     *
     * @param array $relationships
     * @param array &$data
     * @param string $serviceTypeName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $serviceTypeName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'assignees' => ['dbKey' => 'assignee_id', 'lookupColumn' => 'person_id'],
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
                        $command->warn("Service type '{$serviceTypeName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'name' => 'nullable|string',
            'archived_at' => 'nullable|date',
            'description' => 'nullable|string',

            'assignee_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 