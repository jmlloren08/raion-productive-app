<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveBill;
use App\Models\ProductiveComment;
use App\Models\ProductiveDeal;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductiveDocumentType;
use App\Models\ProductiveEmail;
use App\Models\ProductiveExpense;
use App\Models\ProductiveInvoice;
use App\Models\ProductivePage;
use App\Models\ProductiveTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreAttachment extends AbstractAction
{
    /**
     * Required fields that must be present in the attachment data
     */
    protected array $requiredFields = [
        'name',
        'size',
        'url',
        'temp_url',
        'resized',
        'attachable_type',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'creator_id' => ProductivePeople::class,
        'invoice_id' => ProductiveInvoice::class,
        'purchase_order_id' => ProductiveInvoice::class,
        'bill_id' => ProductiveBill::class,
        'email_id' => ProductiveEmail::class,
        'page_id' => ProductivePage::class,
        'expense_id' => ProductiveExpense::class,
        'comment_id' => ProductiveComment::class,
        'task_id' => ProductiveTask::class,
        'document_style_id' => ProductiveDocumentStyle::class,
        'document_type_id' => ProductiveDocumentType::class,
        'deal_id' => ProductiveDeal::class,
    ];

    /**
     * Store an attachment in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $attachmentData = $parameters['attachmentData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$attachmentData) {
            throw new \Exception('Attachment data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing attachment: {$attachmentData['id']}");
            }

            // Validate basic data structure
            if (!isset($attachmentData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $attachmentData['attributes'] ?? [];
            $relationships = $attachmentData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($attachmentData['type'])) {
                $attributes['type'] = $attachmentData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $attachmentData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $attachmentData['id'],
                'type' => $attributes['type'] ?? $attachmentData['type'] ?? 'attachments',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Attachment', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update attachment
            ProductiveAttachment::updateOrCreate(
                ['id' => $attachmentData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored attachment: {$attributes['name']} (ID: {$attachmentData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store attachment {$attachmentData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store attachment {$attachmentData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $attachmentId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $attachmentId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for attachment {$attachmentId}: " . implode(', ', $missingFields);
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
     * @param string $attachmentName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $attachmentName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'creator' => 'creator_id',
            'invoice' => 'invoice_id',
            'purchase_order' => 'purchase_order',
            'bill' => 'bill_id',
            'email' => 'email_id',
            'page' => 'page_id',
            'expense' => 'expense_id',
            'comment' => 'comment_id',
            'document_style' => 'document_style_id',
            'document_type' => 'document_type_id',
            'deal ' => 'deal_id',
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
                        $command->warn("Attachment '{$attachmentName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'content_type' => 'nullable|string',
            'size' => 'required|integer',
            'url' => 'required|string',
            'thumb' => 'nullable|string',
            'temp_url' => 'required|string',
            'resized' => 'required|boolean',
            'created_at_api' => 'nullable|timestamp',
            'deleted_at_api' => 'nullable|timestamp',
            'attachment_type' => 'nullable|string',
            'message_id' => 'nullable|integer',
            'external_id' => 'nullable|string',
            'attachable_type' => 'required|string',

            'creator_id' => 'nullable|string',
            'invoice_id' => 'nullable|string',
            'purchase_order_id' => 'nullable|string',
            'bill_id' => 'nullable|string',
            'email_id' => 'nullable|string',
            'page_id' => 'nullable|string',
            'expense_id' => 'nullable|string',
            'comment_id' => 'nullable|string',
            'task_id' => 'nullable|string',
            'document_style_id' => 'nullable|string',
            'document_type_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 