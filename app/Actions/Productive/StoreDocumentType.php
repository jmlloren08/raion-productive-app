<?php

namespace App\Actions\Productive;

use App\Models\ProductiveDocumentType;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductiveAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreDocumentType extends AbstractAction
{
    /**
     * Required fields that must be present in the document type data
     */
    protected array $requiredFields = [
        'name',
        'tax1_name',
        'tax1_value'
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'subsidiary_id' => ProductiveSubsidiary::class,
        'document_style_id' => ProductiveDocumentStyle::class,
        'attachment_id' => ProductiveAttachment::class
    ];

    /**
     * Store a document type in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $documentTypeData = $parameters['documentTypeData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$documentTypeData) {
            throw new \Exception('Document type data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing document type: {$documentTypeData['id']}");
            }

            // Validate basic data structure
            if (!isset($documentTypeData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $documentTypeData['attributes'] ?? [];
            $relationships = $documentTypeData['relationships'] ?? [];

            // Validate required fields
            $this->validateRequiredFields($attributes, $documentTypeData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $documentTypeData['id'],
                'type' => $attributes['type'] ?? $documentTypeData['type'] ?? 'document_types',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Document Type', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update document type
            ProductiveDocumentType::updateOrCreate(
                ['id' => $documentTypeData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored document type: {$attributes['name']} (ID: {$documentTypeData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store document type {$documentTypeData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store document type {$documentTypeData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $documentTypeId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $documentTypeId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for document type {$documentTypeId}: " . implode(', ', $missingFields);
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
            'template_options',
            'exporter_options',
            'email_data',
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
     * @param string $documentTypeName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $documentTypeName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'subsidiary' => 'subsidiary_id',
            'document_style' => 'document_style_id',
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
                        $command->warn("Document type '{$documentTypeName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'tax1_name' => 'required|string',
            'tax1_value' => 'required|numeric',
            'tax2_name' => 'nullable|string',
            'tax2_value' => 'nullable|numeric',
            'locale' => 'string',
            'document_template_id' => 'nullable|integer',
            'exportable_type_id' => 'nullable|integer',
            'note' => 'nullable|string',
            'footer' => 'nullable|string',
            'template_options' => 'nullable|json',
            'archived_at' => 'nullable|date',
            'header_template' => 'nullable|string',
            'body_template' => 'nullable|string',
            'footer_template' => 'nullable|string',
            'scss_template' => 'nullable|string',
            'exporter_options' => 'nullable|json',
            'email_template' => 'nullable|string',
            'email_subject' => 'nullable|string',
            'email_data' => 'nullable|json',
            'dual_currency' => 'boolean',
            'subsidiary_id' => 'nullable|string',
            'document_style_id' => 'nullable|string',
            'attachment_id' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
