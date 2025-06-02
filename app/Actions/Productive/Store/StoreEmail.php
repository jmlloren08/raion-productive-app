<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveEmail;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveDeal;
use App\Models\ProductiveInvoice;
use App\Models\ProductivePrs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreEmail extends AbstractAction
{
    /**
     * Required fields that must be present in the email data
     */
    protected array $requiredFields = [
        'subject',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'creator_id' => ProductivePeople::class,
        'deal_id' => ProductiveDeal::class,
        'invoice_id' => ProductiveInvoice::class,
        'payment_reminder_sequence_id' => ProductivePrs::class,
        'attachment_id' => ProductiveAttachment::class,
    ];

    /**
     * Store an email in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $emailData = $parameters['emailData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$emailData) {
            throw new \Exception('Email data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing email: {$emailData['id']}");
            }

            // Validate basic data structure
            if (!isset($emailData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $emailData['attributes'] ?? [];
            $relationships = $emailData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($emailData['type'])) {
                $attributes['type'] = $emailData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $emailData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $emailData['id'],
                'type' => $attributes['type'] ?? $emailData['type'] ?? 'emails',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['subject'] ?? 'Unknown Email', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update email
            ProductiveEmail::updateOrCreate(
                ['id' => $emailData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored email: {$attributes['subject']} (ID: {$emailData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store email {$emailData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store email {$emailData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $emailId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $emailId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for email {$emailId}: " . implode(', ', $missingFields);
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
            'thread',
            'integration',
            'email_recipients',
            'recipients',
            'to_recipients',
            'cc_recipients',
            'bcc_recipients',
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
     * @param string $emailSubject
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $emailSubject, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'creator' => 'creator_id',
            'deal' => 'deal_id',
            'invoice' => 'invoice_id',
            'payment_reminder' => 'payment_reminder_sequence_id',
            'attachment' => 'attachment_id',
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
                        $command->warn("Email '{$emailSubject}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'subject' => 'required|string',
            'body' => 'nullable|string',
            'body_truncated' => 'nullable|string',
            'auto_linked' => 'boolean',
            'linked_type' => 'nullable|string',
            'linked_id' => 'nullable|integer',
            'external_id' => 'nullable|string',
            'dismissed_at' => 'nullable|date',
            'created_at_api' => 'nullable|date',
            'delivered_at' => 'nullable|date',
            'received_at' => 'nullable|date',
            'failed_at' => 'nullable|date',
            'outgoing' => 'nullable|boolean',
            'from' => 'nullable|string',

            'creator_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'invoice_id' => 'nullable|string',
            'payment_reminder_sequence_id' => 'nullable|string',
            
            'thread' => 'nullable|json',
            'integration' => 'nullable|json',
            'email_recipients' => 'nullable|json',
            'recipients' => 'nullable|json',
            'to_recipients' => 'nullable|json',
            'cc_recipients' => 'nullable|json',
            'bcc_recipients' => 'nullable|json',
            
            'attachment_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
