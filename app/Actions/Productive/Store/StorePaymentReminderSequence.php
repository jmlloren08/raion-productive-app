<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveDeal;
use App\Models\ProductivePaymentReminder;
use App\Models\ProductivePaymentReminderSequence;
use App\Models\ProductivePeople;
use App\Models\ProductivePrs;
use App\Models\ProductiveSubsidiary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StorePaymentReminderSequence extends AbstractAction
{
    /**
     * Required fields that must be present in the payment reminder sequence data 
     */
    protected array $requiredFields = [
        // No required fields
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'creator_id' => ProductivePeople::class,
        'updater_id' => ProductivePeople::class,
        'payment_reminder_id' => ProductivePaymentReminder::class,
    ];

    /**
     * Execute the action to store a payment reminder sequence from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "payment_reminder_sequences",
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
        $sequenceData = $parameters['sequenceData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$sequenceData) {
            throw new \Exception('Payment reminder sequence data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing payment reminder sequence: {$sequenceData['id']}");
            }

            // Validate basic data structure
            if (!isset($sequenceData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $sequenceData['attributes'] ?? [];
            $relationships = $sequenceData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($sequenceData['type'])) {
                $attributes['type'] = $sequenceData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $sequenceData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $sequenceData['id'],
                'type' => $attributes['type'] ?? $sequenceData['type'] ?? 'payment_reminder_sequences',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown PRS', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update payment reminder sequence
            ProductivePrs::updateOrCreate(
                ['id' => $sequenceData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored payment reminder sequence {$attributes['name']} (ID: {$sequenceData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store payment reminder sequence {$sequenceData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store payment reminder sequence {$sequenceData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $sequenceId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $sequenceId, ?Command $command): void
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
            $message = "Required fields missing for payment reminder sequence {$sequenceId}: " . implode(', ', $missingFields);
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
     * @param string $sequenceId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $sequenceId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'creator' => 'creator_id',
            'updater' => 'updater_id',
            'payment_reminder' => 'payment_reminder_id',
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
                        $command->warn("Payment reminder sequence '{$sequenceId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'name' => 'nullable|string',
            'created_at_api' => 'nullable|date',
            'updated_at_api' => 'nullable|date',
            'default_sequence' => 'boolean',

            'creator_id' => 'nullable|string',
            'updater_id' => 'nullable|string',
            'payment_reminder_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 