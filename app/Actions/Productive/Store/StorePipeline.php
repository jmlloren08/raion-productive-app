<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePipeline;
use App\Models\ProductivePeople;
use App\Models\ProductiveCompany;
use App\Models\ProductiveSubsidiary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StorePipeline extends AbstractAction
{
    /**
     * Required fields that must be present in the pipeline data
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'creator_id' => ProductivePeople::class,
        'updater_id' => ProductivePeople::class,
    ];

    /**
     * Store a pipeline in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $pipelineData = $parameters['pipelineData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$pipelineData) {
            throw new \Exception('Pipeline data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing pipeline: {$pipelineData['id']}");
            }

            // Validate basic data structure
            if (!isset($pipelineData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $pipelineData['attributes'] ?? [];
            $relationships = $pipelineData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($pipelineData['type'])) {
                $attributes['type'] = $pipelineData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $pipelineData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $pipelineData['id'],
                'type' => $attributes['type'] ?? $pipelineData['type'] ?? 'pipelines',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Pipeline', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update pipeline
            ProductivePipeline::updateOrCreate(
                ['id' => $pipelineData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored pipeline: {$attributes['name']} (ID: {$pipelineData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store pipeline {$pipelineData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store pipeline {$pipelineData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $pipelineId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $pipelineId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for pipeline {$pipelineId}: " . implode(', ', $missingFields);
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
     * @param string $pipelineId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $pipelineId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'creator' => 'creator_id',
            'updater' => 'updater_id',
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
                        $command->warn("Pipeline '{$pipelineId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'created_at_api' => 'nullable|timestamp',
            'updated_at_api' => 'nullable|timestamp',
            'position' => 'required|integer',
            'icon_id' => 'required|string',
            'pipeline_type_id' => 'required|integer',

            'creator_id' => 'nullable|string',
            'updater_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 