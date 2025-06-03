<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveActivity;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveComment;
use App\Models\ProductiveDeal;
use App\Models\ProductiveProject;
use App\Models\ProductiveCompany;
use App\Models\ProductiveEmail;
use App\Models\ProductivePeople;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreActivity extends AbstractAction
{
    /**
     * Required fields that must be present in the activity data
     */
    protected array $requiredFields = [
        // No required fields
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'creator_id' => ProductivePeople::class,
        'comment_id' => ProductiveComment::class,
        'email_id' => ProductiveEmail::class,
        'attachment_id' => ProductiveAttachment::class,
    ];

    /**
     * Store an activity in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $activityData = $parameters['activityData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$activityData) {
            throw new \Exception('Activity data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing activity: {$activityData['id']}");
            }

            // Validate basic data structure
            if (!isset($activityData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $activityData['attributes'] ?? [];
            $relationships = $activityData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($activityData['type'])) {
                $attributes['type'] = $activityData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $activityData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $activityData['id'],
                'type' => $attributes['type'] ?? $activityData['type'] ?? 'activities',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['event'] ?? 'Unknown Activity', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update activity
            ProductiveActivity::updateOrCreate(
                ['id' => $activityData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored activity: {$attributes['event']} (ID: {$activityData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store activity {$activityData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store activity {$activityData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $activityId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $activityId, ?Command $command): void
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
            $message = "Required fields missing for activity {$activityId}: " . implode(', ', $missingFields);
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
            'changeset',
            'roles',
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
     * @param string $activityAction
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $activityAction, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'creator' => 'creator_id',
            'comment' => 'comment_id',
            'email' => 'email_id',
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
                        $command->warn("Activity '{$activityAction}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'event' => 'nullable|string',
            'changeset' => 'nullable|array',
            'item_id' => 'nullable|string',
            'item_type' => 'nullable|string',
            'item_name' => 'nullable|string',
            'item_deleted_at' => 'nullable|date',
            'parent_id' => 'nullable|string',
            'parent_type' => 'nullable|string',
            'parent_name' => 'nullable|string',
            'parent_deleted_at' => 'nullable|date',
            'root_id' => 'nullable|string',
            'root_type' => 'nullable|string',
            'root_name' => 'nullable|string',
            'root_deleted_at' => 'nullable|date',
            'deal_is_budget' => 'boolean',
            'task_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'booking_id' => 'nullable|string',
            'invoice_id' => 'nullable|string',
            'company_id' => 'nullable|string',
            'created_at_api' => 'nullable|date',
            'discussion_id' => 'nullable|string',
            'engagement_id' => 'nullable|string',
            'page_id' => 'nullable|string',
            'person_id' => 'nullable|string',
            'purchase_order_id' => 'nullable|string',
            'made_by_automation' => 'boolean',
            
            'creator_id' => 'nullable|string',
            'comment_id' => 'nullable|string',
            'email_id' => 'nullable|string',
            'attachment_id' => 'nullable|string',

            'roles' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
