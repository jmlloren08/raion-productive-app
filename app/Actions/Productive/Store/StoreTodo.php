<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveTask;
use App\Models\ProductivePeople;
use App\Models\ProductiveDeal;
use App\Models\ProductiveTodo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTodo extends AbstractAction
{
    /**
     * Required fields that must be present in the todo data
     */
    protected array $requiredFields = [
        'description',
        'todoable_type',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'assignee_id' => ProductivePeople::class,
        'deal_id' => ProductiveDeal::class,
        'task_id' => ProductiveTask::class,
    ];

    /**
     * Store a todo in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $todoData = $parameters['todoData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$todoData) {
            throw new \Exception('Todo data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing todo: {$todoData['id']}");
            }

            // Validate basic data structure
            if (!isset($todoData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $todoData['attributes'] ?? [];
            $relationships = $todoData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($todoData['type'])) {
                $attributes['type'] = $todoData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $todoData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $todoData['id'],
                'type' => $attributes['type'] ?? $todoData['type'] ?? 'todos',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['description'] ?? 'Unknown Todo', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update todo
            ProductiveTodo::updateOrCreate(
                ['id' => $todoData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored todo: {$attributes['description']} (ID: {$todoData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store todo {$todoData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store todo {$todoData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $todoId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $todoId, ?Command $command): void
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
            $message = "Required fields missing for todo {$todoId}: " . implode(', ', $missingFields);
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
     * @param string $todoTitle
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $todoTitle, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'assignee' => 'assignee_id',
            'deal' => 'deal_id',
            'task' => 'task_id',
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
                        $command->warn("Todo '{$todoTitle}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'description' => 'required|string',
            'closed_at' => 'nullable|date',
            'closed' => 'nullable|boolean',
            'due_date' => 'nullable|date',
            'created_at_api' => 'nullable|date',
            'todoable_type' => 'required|string',
            'due_time' => 'nullable|string',
            'position' => 'nullable|integer',
            
            'assignee_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'task_id' => 'nullable|string',

        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 