<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTag extends AbstractAction
{
    /**
     * Required fields that must be present in the tag data 
     */
    protected array $requiredFields = [
        // No required fields defined by default
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        // No foreign keys defined by default
    ];

    /**
     * Execute the action to store a tag from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "tags",
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
        $tagData = $parameters['tagData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$tagData) {
            throw new \Exception('Tag data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing tag: {$tagData['id']}");
            }

            // Validate basic data structure
            if (!isset($tagData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }
            
            $attributes = $tagData['attributes'] ?? [];
            $relationships = $tagData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($tagData['type'])) {
                $attributes['type'] = $tagData['type'];
            }

            // Prepare base data
            $data = [
                'id' => $tagData['id'],
                'type' => $attributes['type'] ?? $tagData['type'] ?? 'tags',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update tag
            ProductiveTag::updateOrCreate(
                ['tag_id' => $tagData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored tag {$attributes['name']} (ID: {$tagData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store tag {$tagData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store tag {$tagData['id']}: " . $e->getMessage());
            throw $e;
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
            'tag_id' => 'nullable|integer',
            'name' => 'nullable|string',
            'color' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 