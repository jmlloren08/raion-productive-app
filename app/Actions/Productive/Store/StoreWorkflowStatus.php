<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveWorkflow;
use App\Models\ProductiveWorkflowStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreWorkflowStatus extends AbstractAction
{
    /**
     * Required fields that must be present in the workflow status data 
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'workflow_id' => ProductiveWorkflow::class,
    ];

    /**
     * Execute the action to store a workflow status from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "workflow_statuses",
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
        $workflowStatusData = $parameters['workflowStatusData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$workflowStatusData) {
            throw new \Exception('Workflow status data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing workflow status: {$workflowStatusData['id']}");
            }

            // Validate basic data structure
            if (!isset($workflowStatusData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $workflowStatusData['attributes'] ?? [];
            $relationships = $workflowStatusData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($workflowStatusData['type'])) {
                $attributes['type'] = $workflowStatusData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $workflowStatusData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $workflowStatusData['id'],
                'type' => $attributes['type'] ?? $workflowStatusData['type'] ?? 'workflow_statuses',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Workflow Status', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update workflow status
            ProductiveWorkflowStatus::updateOrCreate(
                ['id' => $workflowStatusData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored workflow status {$attributes['name']} (ID: {$workflowStatusData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store workflow status {$workflowStatusData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store workflow status {$workflowStatusData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $workflowStatusId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $workflowStatusId, ?Command $command): void
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
            $message = "Required fields missing for workflow status {$workflowStatusId}: " . implode(', ', $missingFields);
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
     * @param string $workflowStatusId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $workflowStatusId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'workflow' => 'workflow_id',
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
                        $command->warn("Workflow status '{$workflowStatusId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'color_id' => 'nullable|string',
            'position' => 'nullable|integer',
            'category_id' => 'nullable|integer',

            'workflow_id' => 'required|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 