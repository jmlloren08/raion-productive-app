<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveCfo;
use App\Models\ProductivePeople;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveCustomField;
use App\Models\ProductiveProject;
use App\Models\ProductiveSection;
use App\Models\ProductiveSurvey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreCustomField extends AbstractAction
{
    /**
     * Required fields that must be present in the custom field data 
     */
    protected array $requiredFields = [
        'name',
        'customizable_type'
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'project_id' => ProductiveProject::class,
        'section_id' => ProductiveSection::class,
        'survey_id' => ProductiveSurvey::class,
        'person_id' => ProductivePeople::class,
        'cfo_id' => ProductiveCfo::class,
    ];

    /**
     * Execute the action to store a custom field from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "custom_fields",
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
        $customFieldData = $parameters['customFieldData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$customFieldData) {
            throw new \Exception('Custom field data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing custom field: {$customFieldData['id']}");
            }

            // Validate basic data structure
            if (!isset($customFieldData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $customFieldData['attributes'] ?? [];
            $relationships = $customFieldData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($customFieldData['type'])) {
                $attributes['type'] = $customFieldData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $customFieldData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $customFieldData['id'],
                'type' => $attributes['type'] ?? $customFieldData['type'] ?? 'custom_fields',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Custom Field', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update custom field
            ProductiveCustomField::updateOrCreate(
                ['id' => $customFieldData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored custom field {$attributes['name']} (ID: {$customFieldData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store custom field {$customFieldData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store custom field {$customFieldData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $customFieldId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $customFieldId, ?Command $command): void
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
            $message = "Required fields missing for custom field {$customFieldId}: " . implode(', ', $missingFields);
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
     * @param string $customFieldId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $customFieldId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'project' => 'project_id',
            'section' => 'section_id',
            'survey' => 'survey_id',
            'custom_field_people' => 'person_id',
            'options' => 'cfo_id',
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
                        $command->warn("Custom field '{$customFieldId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'created_at_api' => 'nullable|date',
            'updated_at_api' => 'nullable|date',
            'name' => 'required|string',
            'data_type' => 'nullable|integer',
            'required' => 'nullable|boolean',
            'description' => 'nullable|string',
            'archived_at' => 'nullable|date',
            'aggregation_type_id' => 'nullable|integer',
            'formatting_type_id' => 'nullable|integer',
            'global' => 'nullable|boolean',
            'show_in_add_edit_views' => 'nullable|boolean',
            'sensitive' => 'nullable|boolean',
            'position' => 'nullable|integer',
            'quick_add_enabled' => 'nullable|boolean',
            'customizable_type' => 'required|string',
            // Foreign keys
            'project_id' => 'nullable|string',
            'section_id' => 'nullable|string',
            'survey_id' => 'nullable|string',
            'person_id' => 'nullable|string',
            'cfo_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 