<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveComment;
use App\Models\ProductivePeople;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveTask;
use App\Models\ProductiveDeal;
use App\Models\ProductiveProject;
use App\Models\ProductiveBoard;
use App\Models\ProductiveBooking;
use App\Models\ProductiveCompany;
use App\Models\ProductiveDiscussion;
use App\Models\ProductiveInvoice;
use App\Models\ProductivePurchaseOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreComment extends AbstractAction
{
    /**
     * Required fields that must be present in the comment data
     */
    protected array $requiredFields = [
        // No required fields defined, but can be added as needed
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'company_id' => ProductiveCompany::class,
        'creator_id' => ProductivePeople::class,
        'deal_id' => ProductiveDeal::class,
        'discussion_id' => ProductiveDiscussion::class,
        'invoice_id' => ProductiveInvoice::class,
        'person_id' => ProductivePeople::class,
        'pinned_by_id' => ProductivePeople::class,
        'task_id' => ProductiveTask::class,
        'purchase_order_id' => ProductivePurchaseOrder::class,
        'attachment_id' => ProductiveAttachment::class,
    ];

    /**
     * Store a comment in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $commentData = $parameters['commentData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$commentData) {
            throw new \Exception('Comment data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing comment: {$commentData['id']}");
            }

            // Validate basic data structure
            if (!isset($commentData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $commentData['attributes'] ?? [];
            $relationships = $commentData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($commentData['type'])) {
                $attributes['type'] = $commentData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $commentData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $commentData['id'],
                'type' => $attributes['type'] ?? $commentData['type'] ?? 'comments',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['body'] ?? 'Unknown Comment', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update comment
            ProductiveComment::updateOrCreate(
                ['id' => $commentData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored comment: {$attributes['body']} (ID: {$commentData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store comment {$commentData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store comment {$commentData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $commentId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $commentId, ?Command $command): void
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
            $message = "Required fields missing for comment {$commentId}: " . implode(', ', $missingFields);
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
     * @param string $commentContent
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $commentContent, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'company' => 'company_id',
            'creator' => 'creator_id',
            'deal' => 'deal_id',
            'discussion' => 'discussion_id',
            'invoice' => 'invoice_id',
            'person' => 'person_id',
            'pinned_by' => 'pinned_by_id',
            'task' => 'task_id',
            'purchase_order' => 'purchase_order_id',
            'attachments' => 'attachment_id',
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
                        $command->warn("Comment '{$commentContent}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'body' => 'nullable|string',
            'commentable_type' => 'nullable|string',
            'created_at_api' => 'nullable|date',
            'deleted_at_api' => 'nullable|date',
            'draft' => 'boolean',
            'edited_at' => 'nullable|date',
            'hidden' => 'boolean',
            'pinned_at' => 'nullable|date',
            'reactions' => 'nullable|array',
            'updated_at_api' => 'nullable|date',
            'version_number' => 'nullable|integer',

            'company_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'discussion_id' => 'nullable|string',
            'invoice_id' => 'nullable|string',
            'person_id' => 'nullable|string',
            'pinned_by_id' => 'nullable|string',
            'task_id' => 'nullable|string',
            'purchase_order_id' => 'nullable|string',
            'attachment_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
