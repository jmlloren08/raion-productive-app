<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveCfo;
use App\Models\ProductivePeople;
use App\Models\ProductiveCustomField;
use App\Models\ProductiveCustomFieldOption;
use App\Models\ProductiveSection;
use App\Models\ProductiveSurvey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreCustomFieldOption extends AbstractAction
{
    /**
     * Required fields that must be present in the custom field option data 
     */
    protected array $requiredFields = [
        'name',
        'position',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'custom_field_id' => ProductiveCustomField::class,
    ];

    /**
     * Execute the action to store a custom field option from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "custom_field_options",
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
        $customFieldOptionData = $parameters['customFieldOptionData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$customFieldOptionData) {
            throw new \Exception('Custom field option data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing custom field option: {$customFieldOptionData['id']}");
            }

            // Validate basic data structure
            if (!isset($customFieldOptionData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $customFieldOptionData['attributes'] ?? [];
            $relationships = $customFieldOptionData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($customFieldOptionData['type'])) {
                $attributes['type'] = $customFieldOptionData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $customFieldOptionData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $customFieldOptionData['id'],
                'type' => $attributes['type'] ?? $customFieldOptionData['type'] ?? 'custom_field_options',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Custom Field Option', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update custom field option
            ProductiveCfo::updateOrCreate(
                ['id' => $customFieldOptionData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored custom field option {$attributes['name']} (ID: {$customFieldOptionData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store custom field option {$customFieldOptionData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store custom field option {$customFieldOptionData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $customFieldOptionId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $customFieldOptionId, ?Command $command): void
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
            $message = "Required fields missing for custom field option {$customFieldOptionId}: " . implode(', ', $missingFields);
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
            'preferences',
            'metadata'
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
     * @param string $customFieldOptionId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $customFieldOptionId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'custom_field' => 'custom_field_id',
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
                        $command->warn("Custom field option '{$customFieldOptionId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'archived_at' => 'nullable|date',
            'position' => 'required|integer',
            'color_id' => 'nullable|string',
            // Foreign key relationships
            'custom_field_id' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 