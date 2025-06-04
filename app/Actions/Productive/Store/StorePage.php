<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveProject;
use App\Models\ProductiveAttachment;
use App\Models\ProductivePage;
use App\Models\ProductivePeople;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StorePage extends AbstractAction
{
    /**
     * Required fields that must be present in the page data
     */
    protected array $requiredFields = [
        // No required fields defined yet, can be added as needed
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'creator_id' => ProductivePeople::class,
        'project_id' => ProductiveProject::class,
        'attachment_id' => ProductiveAttachment::class,
    ];

    /**
     * Store a page in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $pageData = $parameters['pageData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$pageData) {
            throw new \Exception('Page data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing page: {$pageData['id']}");
            }

            // Validate basic data structure
            if (!isset($pageData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $pageData['attributes'] ?? [];
            $relationships = $pageData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($pageData['type'])) {
                $attributes['type'] = $pageData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $pageData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $pageData['id'],
                'type' => $attributes['type'] ?? $pageData['type'] ?? 'pages',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['title'] ?? 'Unknown Page', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update page
            ProductivePage::updateOrCreate(
                ['id' => $pageData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored page: {$attributes['title']} (ID: {$pageData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store page {$pageData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store page {$pageData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $pageId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $pageId, ?Command $command): void
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
            $message = "Required fields missing for page {$pageId}: " . implode(', ', $missingFields);
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
            'preferences',
            'body',
            'template_object',
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
     * @param string $pageName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $pageName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'creator' => 'creator_id',
            'project' => 'project_id',
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
                        $command->warn("Page '{$pageName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'cover_image_meta' => 'nullable|string',
            'cover_image_url' => 'nullable|string',
            'created_at_api' => 'nullable|date',
            'edited_at_api' => 'nullable|date',
            'icon_id' => 'nullable|integer',
            'position' => 'nullable|integer',
            'preferences' => 'nullable|json',
            'title' => 'nullable|string',
            'updated_at_api' => 'nullable|date',
            'version_number' => 'nullable|integer',
            'last_activity_at' => 'nullable|date',
            'body' => 'nullable|json',
            'parent_page_id' => 'nullable|integer',
            'root_page_id' => 'nullable|integer',
            'public_uuid' => 'nullable|string',
            'public' => 'boolean',
            
            'creator_id' => 'nullable|string',
            'project_id' => 'nullable|string',
            'attachment_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 