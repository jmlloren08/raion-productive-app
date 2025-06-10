<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveTimeEntry;
use App\Models\ProductiveDeal;
use App\Models\ProductiveService;
use App\Models\ProductiveTask;
use App\Models\ProductiveSubsidiary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTimeEntry extends AbstractAction
{
    /**
     * Required fields that must be present in the time entry data 
     */
    protected array $requiredFields = [
        'date',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'person_id' => ProductivePeople::class,
        'service_id' => ProductiveService::class,
        'task_id' => ProductiveTask::class,
        'deal_id' => ProductiveDeal::class,
        'approver_id' => ProductivePeople::class,
        'updater_id' => ProductivePeople::class,
        'rejecter_id' => ProductivePeople::class,
        'creator_id' => ProductivePeople::class,
        'last_actor_id' => ProductivePeople::class,
        'person_subsidiary_id' => ProductiveSubsidiary::class,
        'deal_subsidiary_id' => ProductiveSubsidiary::class
    ];    /**
     * Execute the action to store a time entry from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "time_entries",
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
        $timeEntryData = $parameters['timeEntryData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$timeEntryData) {
            throw new \Exception('Time entry data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing time entry: {$timeEntryData['id']}");
            }

            // Validate basic data structure
            if (!isset($timeEntryData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $timeEntryData['attributes'] ?? [];
            $relationships = $timeEntryData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($timeEntryData['type'])) {
                $attributes['type'] = $timeEntryData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $timeEntryData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $timeEntryData['id'],
                'type' => $attributes['type'] ?? $timeEntryData['type'] ?? 'time_entries',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['date'] ?? 'Unknown Time Entry', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update time entry
            ProductiveTimeEntry::updateOrCreate(
                ['id' => $timeEntryData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored time entry {$attributes['date']} (ID: {$timeEntryData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store time entry {$timeEntryData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store time entry {$timeEntryData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $timeEntryId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $timeEntryId, ?Command $command): void
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
            $message = "Required fields missing for time entry {$timeEntryId}: " . implode(', ', $missingFields);
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
            'custom_fields',
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
     * @param string $timeEntryId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $timeEntryId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'person' => 'person_id',
            'service' => 'service_id',
            'task' => 'task_id',
            'deal' => 'deal_id',
            'approver' => 'approver_id',
            'updater' => 'updater_id',
            'rejecter' => 'rejecter_id',
            'creator' => 'creator_id',
            'last_actor' => 'last_actor_id',
            'person_subsidiary' => 'person_subsidiary_id',
            'deal_subsidiary' => 'deal_subsidiary_id',
            'timesheet' => 'timesheet_id'
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
                        $command->warn("Time entry '{$timeEntryId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'date' => 'nullable|date',
            'time' => 'nullable|integer',
            'billable_time' => 'nullable|integer',
            'note' => 'nullable|string',
            'track_method_id' => 'nullable|integer',
            'started_at' => 'nullable|date',
            'timer_started_at' => 'nullable|date',
            'timer_stopped_at' => 'nullable|date',
            'approved' => 'nullable|boolean',
            'approved_at' => 'nullable|date',
            'calendar_event_id' => 'nullable|string',
            'invoice_attribution_id' => 'nullable|integer',
            'invoiced' => 'nullable|boolean',
            'overhead' => 'nullable|boolean',
            'rejected' => 'nullable|boolean',
            'rejected_reason' => 'nullable|string',
            'rejected_at' => 'nullable|date',
            'last_activity_at' => 'nullable|date',
            'submitted' => 'nullable|boolean',
            'currency' => 'nullable|string|size:3',
            'currency_default' => 'nullable|string|size:3',
            'currency_normalized' => 'nullable|string|size:3',
            // Foreign keys
            'person_id' => 'nullable|string',
            'service_id' => 'nullable|string',
            'task_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'approver_id' => 'nullable|string',
            'updater_id' => 'nullable|string',
            'rejecter_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'last_actor_id' => 'nullable|string',
            'person_subsidiary_id' => 'nullable|string',
            'deal_subsidiary_id' => 'nullable|string',
            'timesheet_id' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    
}
