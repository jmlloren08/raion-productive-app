<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveInvoice;
use App\Models\ProductivePaymentReminder;
use App\Models\ProductivePeople;
use App\Models\ProductivePrs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StorePaymentReminder extends AbstractAction
{
    /**
     * Required fields that must be present in the payment reminder data 
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
        'invoice_id' => ProductiveInvoice::class,
        'prs_id' => ProductivePrs::class,
    ];

    /**
     * Execute the action to store a payment reminder from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "payment_reminders",
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
        $reminderData = $parameters['reminderData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$reminderData) {
            throw new \Exception('Payment reminder data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing payment reminder: {$reminderData['id']}");
            }

            // Validate basic data structure
            if (!isset($reminderData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $reminderData['attributes'] ?? [];
            $relationships = $reminderData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($reminderData['type'])) {
                $attributes['type'] = $reminderData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $reminderData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $reminderData['id'],
                'type' => $attributes['type'] ?? $reminderData['type'] ?? 'payment_reminders',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['created_at'] ?? 'Unknown PR', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update payment reminder
            ProductivePaymentReminder::updateOrCreate(
                ['payment_reminder_id' => $reminderData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored payment reminder {$attributes['created_at']} (ID: {$reminderData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store payment reminder {$reminderData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store payment reminder {$reminderData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $reminderId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $reminderId, ?Command $command): void
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
            $message = "Required fields missing for payment reminder {$reminderId}: " . implode(', ', $missingFields);
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
            'to',
            'from',
            'cc',
            'bcc',
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
     * @param string $reminderId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $reminderId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'creator' => ['dbKey' => 'creator_id', 'lookupColumn' => 'person_id'],
            'updater' => ['dbKey' => 'updater_id', 'lookupColumn' => 'person_id'],
            'invoice' => ['dbKey' => 'invoice_id', 'lookupColumn' => 'invoice_id'],
            'payment_reminder_sequence' => ['dbKey' => 'prs_id', 'lookupColumn' => 'prs_id'],
        ];

        foreach ($relationshipMap as $apiKey => $config) {
            if (isset($relationships[$apiKey]['data']['id'])) {
                $id = $relationships[$apiKey]['data']['id'];
                if ($command) {
                    $command->info("Processing relationship {$apiKey} with ID: {$id}");
                }

                // Get the model class for this relationship
                $modelClass = $this->foreignKeys[$config['dbKey']];

                if (!$modelClass::where($config['lookupColumn'], $id)->exists()) {
                    if ($command) {
                        $command->warn("Payment reminder '{$reminderId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
                    }
                    $data[$config['dbKey']] = null;
                } else {
                    $data[$config['dbKey']] = $id;
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
            'created_at_api' => 'nullable|date',
            'updated_at_api' => 'nullable|date',
            'subject' => 'nullable|string',
            'subject_parsed' => 'nullable|string',
            'to' => 'nullable|json',
            'from' => 'nullable|string',
            'cc' => 'nullable|json',
            'bcc' => 'nullable|json',
            'body' => 'nullable|string',
            'body_parsed' => 'nullable|string',
            'scheduled_on' => 'nullable|date',
            'sent_at' => 'nullable|date',
            'delivered_at' => 'nullable|date',
            'failed_at' => 'nullable|date',
            'stopped_at' => 'nullable|date',
            'before_due_date' => 'nullable|boolean',
            'reminder_period' => 'nullable|integer',
            'reminder_stopped_reason_id' => 'nullable|integer',

            'creator_id' => 'nullable|string',
            'updater_id' => 'nullable|string',
            'invoice_id' => 'nullable|string',
            'prs_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 