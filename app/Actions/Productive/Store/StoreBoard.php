<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveBoard;
use App\Models\ProductiveProject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreBoard extends AbstractAction
{
    /**
     * Required fields that must be present in the board data
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'project_id' => ProductiveProject::class,
    ];

    /**
     * Store a board in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $boardData = $parameters['boardData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$boardData) {
            throw new \Exception('Board data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing board: {$boardData['id']}");
            }

            // Validate basic data structure
            if (!isset($boardData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $boardData['attributes'] ?? [];
            $relationships = $boardData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($boardData['type'])) {
                $attributes['type'] = $boardData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $boardData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $boardData['id'],
                'type' => $attributes['type'] ?? $boardData['type'] ?? 'boards',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Board', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update board
            ProductiveBoard::updateOrCreate(
                ['id' => $boardData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored board: {$attributes['name']} (ID: {$boardData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store board {$boardData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store board {$boardData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $boardId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $boardId, ?Command $command): void
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
            $message = "Required fields missing for board {$boardId}: " . implode(', ', $missingFields);
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
     * @param string $boardName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $boardName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'project' => 'project_id',
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
                        $command->warn("Board '{$boardName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'placement' => 'nullable|integer',
            'archived_at' => 'nullable|date',

            'project_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 