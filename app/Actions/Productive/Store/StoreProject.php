<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductivePeople;
use App\Models\ProductiveWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreProject extends AbstractAction
{
    /**
     * Required fields that must be present in the project data
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'company_id' => ProductiveCompany::class,
        'project_manager_id' => ProductivePeople::class,
        'last_actor_id' => ProductivePeople::class,
        'workflow_id' => ProductiveWorkflow::class
    ];

    /**
     * Store a project in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $projectData = $parameters['projectData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$projectData) {
            throw new \Exception('Project data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing project: {$projectData['id']}");
            }

            // Validate basic data structure
            if (!isset($projectData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $projectData['attributes'] ?? [];
            $relationships = $projectData['relationships'] ?? [];

            // Debug log relationships
            // if ($command instanceof Command) {
            //     $command->info("Project relationships: " . json_encode($relationships));
            // }

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($projectData['type'])) {
                $attributes['type'] = $projectData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $projectData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $projectData['id'],
                'type' => $attributes['type'] ?? $projectData['type'] ?? 'projects',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Project', $command);

            // Debug log final data
            // if ($command instanceof Command) {
            //     $command->info("Final project data: " . json_encode($data));
            // }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update project
            ProductiveProject::updateOrCreate(
                ['id' => $projectData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored project: {$attributes['name']} (ID: {$projectData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store project {$projectData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store project {$projectData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $projectId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $projectId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for project {$projectId}: " . implode(', ', $missingFields);
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
            'tag_colors',
            'custom_fields',
            'task_custom_fields_ids',
            'task_custom_fields_positions',
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
     * @param string $projectName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $projectName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'company' => 'company_id',
            'project_manager' => 'project_manager_id',
            'last_actor' => 'last_actor_id',
            'workflow' => 'workflow_id'
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
                        $command->warn("Project '{$projectName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'number' => 'required|string',
            'project_number' => 'required|string',
            'project_type_id' => 'required|integer',
            'project_color_id' => 'required|integer',
            'public_access' => 'boolean',
            'time_on_tasks' => 'boolean',
            'template' => 'boolean',
            'sample_data' => 'boolean',
            'preferences' => 'nullable|json',
            'tag_colors' => 'nullable|json',
            'custom_fields' => 'nullable|json',
            'task_custom_fields_ids' => 'nullable|json',
            'task_custom_fields_positions' => 'nullable|json',
            'company_id' => 'nullable|string',
            'project_manager_id' => 'nullable|string',
            'last_actor_id' => 'nullable|string',
            'workflow_id' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
