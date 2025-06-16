<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveTimeSheet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTimeSheet extends AbstractAction
{
    /**
     * Required fields that must be present in the time sheet data 
     */
    protected array $requiredFields = [
        'date',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'person_id' => ProductivePeople::class,
        'creator_id' => ProductivePeople::class,
    ];

    /**
     * Execute the action to store a time sheet from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "time_sheets",
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
        $timeSheetData = $parameters['timeSheetData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$timeSheetData) {
            throw new \Exception('Time sheet data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing time sheet: {$timeSheetData['id']}");
            }

            // Validate basic data structure
            if (!isset($timeSheetData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $timeSheetData['attributes'] ?? [];
            $relationships = $timeSheetData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($timeSheetData['type'])) {
                $attributes['type'] = $timeSheetData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $timeSheetData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $timeSheetData['id'],
                'type' => $attributes['type'] ?? $timeSheetData['type'] ?? 'timesheets',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['date'] ?? 'Unknown Time Sheet', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update time sheet
            ProductiveTimeSheet::updateOrCreate(
                ['id' => $timeSheetData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored time sheet {$attributes['date']} (ID: {$timeSheetData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store time sheet {$timeSheetData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store time sheet {$timeSheetData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $timeSheetId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $timeSheetId, ?Command $command): void
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
            $message = "Required fields missing for time sheet {$timeSheetId}: " . implode(', ', $missingFields);
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
     * @param string $timeSheetId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $timeSheetId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'person' => 'person_id',
            'creator' => 'creator_id',
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
                        $command->warn("Time sheet '{$timeSheetId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'date' => 'nullable|date',
            'created_at_api' => 'nullable|date',

            'person_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 