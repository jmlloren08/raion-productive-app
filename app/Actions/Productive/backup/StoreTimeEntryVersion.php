<?php

namespace App\Actions\Productive;

use App\Models\ProductivePeople;
use App\Models\ProductiveTimeEntryVersion;
use App\Models\ProductivePerson;
use Illuminate\Console\Command;
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
    ];    /**
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
     * @return void
     * @throws \Exception
     */
    public function handle(array $parameters = []): void
    {
        $timeEntryVersionData = $parameters['timeEntryVersionData'] ?? null;
        $command = $parameters['command'] ?? null;

        // Validate basic data structure
        if (!isset($timeEntryVersionData['id'])) {
            throw new \Exception("Missing required field 'id' in root data object");
        }
        
        $attributes = $timeEntryVersionData['attributes'] ?? [];
        $relationships = $timeEntryVersionData['relationships'] ?? [];

        // Validate required fields in attributes
        $this->validateRequiredFields($attributes, $timeEntryVersionData['id'], $command);

        // Prepare base data
        $data = [
            'id' => $timeEntryVersionData['id'],
            'type' => $timeEntryVersionData['type'] ?? 'time_entry_versions',
        ];

        // Add all attributes with safe fallbacks
        foreach ($attributes as $key => $value) {
            $data[$key] = $value;
        }

        // Handle JSON fields
        $this->handleJsonFields($data);

        // Handle foreign key relationships
        $this->handleForeignKeys($relationships, $data, $timeEntryVersionData['id'], $command);

        // Validate data types
        $this->validateDataTypes($data);

        try {
            ProductiveTimeEntryVersion::updateOrCreate(
                ['id' => $timeEntryVersionData['id']],
                $data
            );
            
            if ($command) {
                if ($data['item_id']) {
                    $command->info("Stored time entry version (ID: {$timeEntryVersionData['id']}) for time entry ID: {$data['item_id']}");
                } else {
                    $command->info("Stored time entry version (ID: {$timeEntryVersionData['id']})");
                }
            }
        } catch (\Exception $e) {
            if ($command) {
                $command->error("Failed to store time entry version (ID: {$timeEntryVersionData['id']}): " . $e->getMessage());
                $command->warn("Version data: " . json_encode([
                    'id' => $timeEntryVersionData['id'],
                    'event' => $attributes['event'] ?? 'Unknown Event',
                    'item_type' => $attributes['item_type'] ?? 'Unknown Type'
                ]));
            }
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
        foreach ($this->foreignKeys as $key => $modelClass) {
            if (isset($relationships[$key]['data']['id'])) {
                $id = $relationships[$key]['data']['id'];
                if (!$modelClass::where('id', $id)->exists()) {
                    if ($command) {
                        $command->warn("Time entry version '{$versionId}' is linked to {$key}: {$id}, but this record doesn't exist in our database.");
                    }
                    $data[$key] = null;
                } else {
                    $data[$key] = $id;
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
