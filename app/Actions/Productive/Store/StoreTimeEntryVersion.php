<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveTimeEntryVersion;
use App\Models\ProductivePerson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTimeEntryVersion extends AbstractAction
{
    /**
     * Required fields that must be present in the time entry version data
     */
    protected array $requiredFields = [
        'event',
        'object_changes',
        'item_type',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'creator_id' => ProductivePeople::class
    ];

    /**
     * Execute the action to store a time entry version from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "time_entry_versions",
     *     "attributes": {
     *         "event": string,
     *         "item_type": string,
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
        $timeEntryVersionData = $parameters['timeEntryVersionData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$timeEntryVersionData) {
            throw new \Exception('Time entry version data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing time entry version: {$timeEntryVersionData['id']}");
            }

            // Validate basic data structure
            if (!isset($timeEntryVersionData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $timeEntryVersionData['attributes'] ?? [];
            $relationships = $timeEntryVersionData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($timeEntryVersionData['type'])) {
                $attributes['type'] = $timeEntryVersionData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $timeEntryVersionData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $timeEntryVersionData['id'],
                'type' => $attributes['type'] ?? $timeEntryVersionData['type'] ?? 'time_entry_versions',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['event'] ?? 'Unknown Time Entry Version', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update time entry version
            ProductiveTimeEntryVersion::updateOrCreate(
                ['id' => $timeEntryVersionData['id']],
                $data
            );

            if ($command instanceof Command) {
                if ($data['item_id']) {
                    $command->info("Successfully stored time entry version: {$attributes['event']} (ID: {$timeEntryVersionData['id']}) for time entry ID: {$data['item_id']}");
                } else {
                    $command->info("Successfully stored time entry version: {$attributes['event']} (ID: {$timeEntryVersionData['id']})");
                }
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store time entry version {$timeEntryVersionData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store time entry version {$timeEntryVersionData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $versionId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $versionId, ?Command $command): void
    {
        // Skip validation if no required fields are defined
        if (empty($this->requiredFields)) {
            return;
        }

        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for time entry version {$versionId}: " . implode(', ', $missingFields);
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
            'object_changes',
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
     * @param string $versionId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $versionId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'creator' => 'creator_id'
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
                        $command->warn("Time entry version '{$versionId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'event' => 'required|string',
            'object_changes' => 'required|json',
            'item_type' => 'required|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
