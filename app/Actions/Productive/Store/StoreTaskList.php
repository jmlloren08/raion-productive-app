<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveBoard;
use App\Models\ProductiveProject;
use App\Models\ProductiveTaskList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTaskList extends AbstractAction
{
    /**
     * Required fields that must be present in the task list data
     */
    protected array $requiredFields = [
        'name',
        'placement',
        'email_key',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'project_id' => ProductiveProject::class,
        'board_id' => ProductiveBoard::class, 
    ];

    /**
     * Store a task list in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $taskListData = $parameters['taskListData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$taskListData) {
            throw new \Exception('Task list data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing task list: {$taskListData['id']}");
            }

            // Validate basic data structure
            if (!isset($taskListData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $taskListData['attributes'] ?? [];
            $relationships = $taskListData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($taskListData['type'])) {
                $attributes['type'] = $taskListData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $taskListData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $taskListData['id'],
                'type' => $attributes['type'] ?? $taskListData['type'] ?? 'task_lists',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Task List', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update task list
            ProductiveTaskList::updateOrCreate(
                ['id' => $taskListData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored task list: {$attributes['name']} (ID: {$taskListData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store task list {$taskListData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store task list {$taskListData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $taskListId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $taskListId, ?Command $command): void
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
            $message = "Required fields missing for task list {$taskListId}: " . implode(', ', $missingFields);
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
     * @param string $taskListName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $taskListName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'project' => 'project_id',
            'board' => 'board_id',
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
                        $command->warn("Task List '{$taskListName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'position' => 'nullable|integer',
            'placement' => 'required|integer',
            'archived_at' => 'nullable|date',
            'email_key' => 'required|string',

            'project_id' => 'nullable|string',
            'board_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 