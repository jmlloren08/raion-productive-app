<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveDiscussion;
use App\Models\ProductiveCompany;
use App\Models\ProductivePeople;
use App\Models\ProductiveProject;
use App\Models\ProductiveTask;
use App\Models\ProductiveDeal;
use App\Models\ProductiveAttachment;
use App\Models\ProductivePage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreDiscussion extends AbstractAction
{
    /**
     * Required fields that must be present in the discussion data
     */
    protected array $requiredFields = [
        // No required fields
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'page_id' => ProductivePage::class,
    ];

    /**
     * Store a discussion in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $discussionData = $parameters['discussionData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$discussionData) {
            throw new \Exception('Discussion data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing discussion: {$discussionData['id']}");
            }

            // Validate basic data structure
            if (!isset($discussionData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $discussionData['attributes'] ?? [];
            $relationships = $discussionData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($discussionData['type'])) {
                $attributes['type'] = $discussionData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $discussionData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $discussionData['id'],
                'type' => $attributes['type'] ?? $discussionData['type'] ?? 'discussions',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['excerpt'] ?? 'Unknown Discussion', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update discussion
            ProductiveDiscussion::updateOrCreate(
                ['id' => $discussionData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored discussion: {$attributes['excerpt']} (ID: {$discussionData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store discussion {$discussionData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store discussion {$discussionData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $discussionId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $discussionId, ?Command $command): void
    {
        // Check for missing required fields
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for discussion {$discussionId}: " . implode(', ', $missingFields);
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
     * @param string $discussionTitle
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $discussionTitle, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'page' => 'page_id',
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
                        $command->warn("Discussion '{$discussionTitle}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'excerpt' => 'nullable|string',
            'resolved_at' => 'nullable|date',
            'subscriber_ids' => 'nullable|array',

            'page_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 