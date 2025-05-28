<?php

namespace App\Actions\Productive;

use App\Models\ProductiveDocumentStyle;
use App\Models\ProductiveAttachment;
use Illuminate\Console\Command;
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
     * Execute the action to store a document style from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "document_styles",
     *     "attributes": {
     *         "name": string,
     *         ...
     *     }
     * }
     *
     * @param array $parameters
     * @return void
     * @throws \Exception
     */
    public function handle(array $parameters = []): void
    {
        $documentStyleData = $parameters['documentStyleData'] ?? null;
        $command = $parameters['command'] ?? null;

        // Validate basic data structure
        if (!isset($documentStyleData['id'])) {
            throw new \Exception("Missing required field 'id' in root data object");
        }
        if (!$documentStyleData) {
            throw new \Exception("No document style data provided");
        }

        try {
            // Extract main components
            $attributes = $documentStyleData['attributes'] ?? [];
            $relationships = $documentStyleData['relationships'] ?? [];

            // Validate required fields
            $this->validateRequiredFields($attributes, $documentStyleData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $documentStyleData['id'],
                'type' => $documentStyleData['type'] ?? 'document_styles',
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

            try {
                ProductiveDocumentStyle::updateOrCreate(
                    ['id' => $documentStyleData['id']],
                    $data
                );

                if ($command instanceof Command) {
                    $command->info("Stored document style '{$attributes['name']}' (ID: {$documentStyleData['id']})");
                }
            } catch (\Exception $e) {
                if ($command instanceof Command) {
                    $command->error("Failed to store document style '{$attributes['name']}' (ID: {$documentStyleData['id']})");
                    $command->error("Error: " . $e->getMessage());
                    $command->warn("Document style data: " . json_encode($data));
                }
                throw $e;
            }
        } catch (ValidationException $e) {
            if ($command instanceof Command) {
                foreach ($e->errors() as $field => $errors) {
                    $command->error("Validation error for {$field}: " . implode(', ', $errors));
                }
            }
            throw $e;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error storing document style: " . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Validate required fields are present
     *
     * @param array $attributes
     * @param string $id
     * @param Command|null $command
     * @throws ValidationException
     */
    protected function validateRequiredFields(array $attributes, string $id, ?Command $command): void
    {
        $missing = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            $message = "Document style {$id} is missing required fields: " . implode(', ', $missing);
            if ($command instanceof Command) {
                $command->error($message);
            }
            throw ValidationException::withMessages([
                'required_fields' => [$message]
            ]);
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
        foreach ($this->foreignKeys as $key => $modelClass) {
            if (isset($relationships[$key]['data']['id'])) {
                $id = $relationships[$key]['data']['id'];
                if (!$modelClass::where('id', $id)->exists()) {
                    if ($command) {
                        $command->warn("Document style '{$documentStyleName}' is linked to {$key}: {$id}, but this record doesn't exist in our database.");
                    }
                    $data[$key] = null;
                } else {
                    $data[$key] = $id;
                }
            }
        }
    }

    /**
     * Validate data types for document style fields
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateDataTypes(array $data): void
    {
        $rules = [
            'name' => 'required|string',
            'styles' => 'nullable|array',
            'type' => 'string',
            'attachment_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }
}
