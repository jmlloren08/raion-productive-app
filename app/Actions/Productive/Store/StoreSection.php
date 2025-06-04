<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveDeal;
use App\Models\ProductiveSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\json;

class StoreSection extends AbstractAction
{
    /**
     * Required fields that must be present in the section data
     */
    protected array $requiredFields = [
        // No required fields defined yet, can be added as needed
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'deal_id' => ProductiveDeal::class,
    ];

    /**
     * Store a section in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $sectionData = $parameters['sectionData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$sectionData) {
            throw new \Exception('Section data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing section: {$sectionData['id']}");
            }

            // Validate basic data structure
            if (!isset($sectionData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $sectionData['attributes'] ?? [];
            $relationships = $sectionData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($sectionData['type'])) {
                $attributes['type'] = $sectionData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $sectionData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $sectionData['id'],
                'type' => $attributes['type'] ?? $sectionData['type'] ?? 'sections',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Section', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update section
            ProductiveSection::updateOrCreate(
                ['id' => $sectionData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored section: {$attributes['name']} (ID: {$sectionData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store section {$sectionData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store section {$sectionData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $sectionId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $sectionId, ?Command $command): void
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
            $message = "Required fields missing for section {$sectionId}: " . implode(', ', $missingFields);
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
     * @param string $sectionName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $sectionName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
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
                        $command->warn("Section '{$sectionName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'preferences' => 'nullable|json',
            'editor_config' => 'nullable|json',
            'position' => 'nullable|integer',
            
            'deal_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 