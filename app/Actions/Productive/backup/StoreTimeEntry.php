<?php

namespace App\Actions\Productive;

use App\Models\ProductivePeople;
use App\Models\ProductiveTimeEntry;
use App\Models\ProductiveDeal;
use App\Models\ProductiveService;
use App\Models\ProductiveTask;
use App\Models\ProductiveSubsidiary;
use Illuminate\Console\Command;
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
     * @return void
     * @throws \Exception
     */
    public function handle(array $parameters = []): void
    {
        $timeEntryData = $parameters['timeEntryData'] ?? null;
        $command = $parameters['command'] ?? null;

        // Validate basic data structure
        if (!isset($timeEntryData['id'])) {
            throw new \Exception("Missing required field 'id' in root data object");
        }
        
        $attributes = $timeEntryData['attributes'] ?? [];
        $relationships = $timeEntryData['relationships'] ?? [];

        // Validate required fields in attributes
        $this->validateRequiredFields($attributes, $timeEntryData['id'], $command);

        // Prepare base data
        $data = [
            'id' => $timeEntryData['id'],
            'type' => $timeEntryData['type'] ?? 'time_entries',
        ];

        // Add all attributes with safe fallbacks
        foreach ($attributes as $key => $value) {
            $data[$key] = $value;
        }

        // Handle foreign key relationships
        $this->handleForeignKeys($relationships, $data, $timeEntryData['id'], $command);

        // Validate data types
        $this->validateDataTypes($data);

        try {
            ProductiveTimeEntry::updateOrCreate(
                ['id' => $timeEntryData['id']],
                $data
            );
            
            if ($command) {
                $command->info("Stored time entry (ID: {$timeEntryData['id']})");
            }
        } catch (\Exception $e) {
            if ($command) {
                $command->error("Failed to store time entry (ID: {$timeEntryData['id']}): " . $e->getMessage());
                $command->warn("Time entry data: " . json_encode([
                    'id' => $timeEntryData['id'],
                    'date' => $attributes['date'] ?? 'Unknown Date',
                    'time' => $attributes['time'] ?? 0
                ]));
            }
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
     * Handle foreign key relationships
     *
     * @param array $relationships
     * @param array &$data
     * @param string $timeEntryId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $timeEntryId, ?Command $command): void
    {
        foreach ($this->foreignKeys as $key => $modelClass) {
            if (isset($relationships[$key]['data']['id'])) {
                $id = $relationships[$key]['data']['id'];
                if (!$modelClass::where('id', $id)->exists()) {
                    if ($command) {
                        $command->warn("Time entry '{$timeEntryId}' is linked to {$key}: {$id}, but this record doesn't exist in our database.");
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
            'calendar_event_id' => 'nullable|integer',
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
