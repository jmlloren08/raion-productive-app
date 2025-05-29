<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveDealStatus;
use App\Models\ProductivePipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreDealStatus extends AbstractAction
{
    /**
     * Required fields that must be present in the deal status data
     */
    protected array $requiredFields = [
        'name',
        'position',
        'status_id',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'pipeline_id' => ProductivePipeline::class
    ];

    /**
     * Store a deal status in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $dealStatusData = $parameters['dealStatusData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$dealStatusData) {
            throw new \Exception('Deal status data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing deal status: {$dealStatusData['id']}");
            }

            // Validate basic data structure
            if (!isset($dealStatusData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $dealStatusData['attributes'] ?? [];
            $relationships = $dealStatusData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($dealStatusData['type'])) {
                $attributes['type'] = $dealStatusData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $dealStatusData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $dealStatusData['id'],
                'type' => $attributes['type'] ?? $dealStatusData['type'] ?? 'deal_statuses',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Deal Status', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update deal status
            ProductiveDealStatus::updateOrCreate(
                ['id' => $dealStatusData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored deal status: {$attributes['name']} (ID: {$dealStatusData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store deal status {$dealStatusData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store deal status {$dealStatusData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $dealStatusId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $dealStatusId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for deal status {$dealStatusId}: " . implode(', ', $missingFields);
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
     * @param string $dealStatusName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $dealStatusName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'pipeline' => 'pipeline_id',
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
                        $command->warn("Deal status '{$dealStatusName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'position' => 'required|integer',
            'color_id' => 'nullable|integer',
            'time_tracking_enabled' => 'boolean',
            'expense_tracking_enabled' => 'boolean',
            'booking_tracking_enabled' => 'boolean',
            'status_id' => 'required|integer',
            'probability_enabled' => 'boolean',
            'probability' => 'nullable|numeric|min:0|max:100',
            'lost_reason_enabled' => 'boolean',
            'used' => 'boolean',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 