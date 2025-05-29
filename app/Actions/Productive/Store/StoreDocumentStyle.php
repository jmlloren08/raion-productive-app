<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductiveAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreDocumentStyle extends AbstractAction
{
    /**
     * Required fields that must be present in the document style data
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'attachment_id' => ProductiveAttachment::class
    ];

    /**
     * Store a document style in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $documentStyleData = $parameters['documentStyleData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$documentStyleData) {
            throw new \Exception('Document style data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing document style: {$documentStyleData['id']}");
            }

            // Validate basic data structure
            if (!isset($documentStyleData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $documentStyleData['attributes'] ?? [];
            $relationships = $documentStyleData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($documentStyleData['type'])) {
                $attributes['type'] = $documentStyleData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $documentStyleData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $documentStyleData['id'],
                'type' => $attributes['type'] ?? $documentStyleData['type'] ?? 'document_styles',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Document Style', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update document style
            ProductiveDocumentStyle::updateOrCreate(
                ['id' => $documentStyleData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored document style: {$attributes['name']} (ID: {$documentStyleData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store document style {$documentStyleData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store document style {$documentStyleData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $documentStyleId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $documentStyleId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for document style {$documentStyleId}: " . implode(', ', $missingFields);
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
            'styles',
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
     * @param string $documentStyleName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $documentStyleName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'attachments' => 'attachment_id'
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
                        $command->warn("Document style '{$documentStyleName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'styles' => 'nullable|json',

            'attachment_id' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
