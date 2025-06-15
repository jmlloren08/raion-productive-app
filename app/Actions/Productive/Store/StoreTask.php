<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveTaskList;
use App\Models\ProductiveProject;
use App\Models\ProductivePeople;
use App\Models\ProductiveTask;
use App\Models\ProductiveWorkflowStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTask extends AbstractAction
{
    /**
     * Required fields that must be present in the task data
     */
    protected array $requiredFields = [
        'title',
        'number',
        'task_number',
        'email_key',
        'type_id',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'project_id' => ProductiveProject::class,
        'creator_id' => ProductivePeople::class,
        'assignee_id' => ProductivePeople::class,
        'last_actor_id' => ProductivePeople::class,
        'task_list_id' => ProductiveTaskList::class,
        'parent_task_id' => ProductiveTask::class,
        'workflow_status_id' => ProductiveWorkflowStatus::class, 
        'attachment_id' => ProductiveAttachment::class,
    ];

    /**
     * Store a task in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $taskData = $parameters['taskData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$taskData) {
            throw new \Exception('Task data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing task: {$taskData['id']}");
            }

            // Validate basic data structure
            if (!isset($taskData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $taskData['attributes'] ?? [];
            $relationships = $taskData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($taskData['type'])) {
                $attributes['type'] = $taskData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $taskData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $taskData['id'],
                'type' => $attributes['type'] ?? $taskData['type'] ?? 'tasks',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['title'] ?? 'Unknown Task', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update task
            ProductiveTask::updateOrCreate(
                ['id' => $taskData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored task: {$attributes['title']} (ID: {$taskData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store task {$taskData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store task {$taskData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $taskId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $taskId, ?Command $command): void
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
            $message = "Required fields missing for task {$taskId}: " . implode(', ', $missingFields);
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
            'repeat_on_weekday',
            'custom_fields',
            'todo_assignee_ids',
            'tag_list',
            'repeated_task',
            'custom_field_people',
            'custom_field_attachments',
        ];

        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                if(is_array($data[$field])) {
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
     * @param string $taskName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $taskName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'project' => 'project_id',
            'creator' => 'creator_id',
            'assignee' => 'assignee_id',
            'last_actor' => 'last_actor_id',
            'task_list' => 'task_list_id',
            'parent_task' => 'parent_task_id',
            'workflow_status' => 'workflow_status_id',
            'attachments' => 'attachment_id',
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
                        $command->warn("Task '{$taskName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'title' => 'required|string',
            'description' => 'nullable|string',
            'number' => 'required|string',
            'task_number' => 'required|string',
            'private' => 'boolean',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'closed_at' => 'nullable|date',
            'created_at_api' => 'nullable|date',
            'updated_at_api' => 'nullable|date',
            'repeat_schedule_id' => 'nullable|integer',
            'repeat_on_interval' => 'nullable|integer',
            'repeat_on_monthday' => 'nullable|integer',
            'repeat_on_weekday' => 'nullable|json',
            'repeat_on_date' => 'nullable|date',
            'repeat_origin_id' => 'nullable|integer',
            'email_key' => 'required|string',
            'custom_fields' => 'nullable|json',
            'todo_count' => 'nullable|integer',
            'open_todo_count' => 'nullable|integer',
            'subtask_count' => 'nullable|integer',
            'open_subtask_count' => 'nullable|integer',
            'creation_method_id' => 'required|integer',
            'todo_assignee_ids' => 'nullable|json',
            'task_dependency_count' => 'integer',
            'type_id' => 'required|integer',
            'blocking_dependency_count' => 'integer',
            'waiting_on_dependency_count' => 'integer',
            'linked_dependency_count' => 'integer',
            'placement' => 'required|integer',
            'subtask_placement' => 'nullable|integer',
            'closed' => 'boolean',
            'due_time' => 'nullable|string',
            'tag_list' => 'nullable|json',
            'last_activity_at' => 'nullable|date',
            'initial_estimate' => 'nullable|integer',
            'remaining_time' => 'nullable|integer',
            'billable_time' => 'nullable|integer',
            'worked_time' => 'nullable|integer',
            'deleted_at_api' => 'nullable|date',
            
            'project_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'assignee_id' => 'nullable|string',
            'last_actor_id' => 'nullable|string',
            'task_list_id' => 'nullable|string',
            'parent_task_id' => 'nullable|string',
            'workflow_status_id' => 'nullable|string',

            'repeated_task' => 'nullable|json',
            
            'attachment_id' => 'nullable|string',

            'custom_field_people' => 'nullable|json',
            'custom_field_attachments' => 'nullable|json',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 