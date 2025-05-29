<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveWorkflow;
use App\Models\ProductiveWorkflowStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreWorkflow extends AbstractAction
{
    /**
     * Required fields that must be present in the workflow data
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'workflow_status_id' => ProductiveWorkflowStatus::class
    ];

    /**
     * Store a workflow in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $workflowData = $parameters['workflowData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$workflowData) {
            throw new \Exception('Workflow data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing workflow: {$workflowData['id']}");
            }

            // Validate basic data structure
            if (!isset($workflowData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $workflowData['attributes'] ?? [];
            $relationships = $workflowData['relationships'] ?? [];

            // Debug log relationships
            // if ($command instanceof Command) {
            //     $command->info("Workflow relationships: " . json_encode($relationships));
            // }

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($workflowData['type'])) {
                $attributes['type'] = $workflowData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $workflowData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $workflowData['id'],
                'type' => $attributes['type'] ?? $workflowData['type'] ?? 'workflow',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Workflow', $command);

            // Debug log final data
            // if ($command instanceof Command) {
            //     $command->info("Final workflow data: " . json_encode($data));
            // }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update workflow
            ProductiveWorkflow::updateOrCreate(
                ['id' => $workflowData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored workflow: {$attributes['name']} (ID: {$workflowData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store workflow {$workflowData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store workflow {$workflowData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $workflowId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $workflowId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for workflow {$workflowId}: " . implode(', ', $missingFields);
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
     * @param string $workflowName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $workflowName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'workflow_statuses' => 'workflow_status_id'
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
                        $command->warn("Workflow '{$workflowName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'archived_at' => 'nullable|date',
            'workflow_status_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
