<?php

namespace App\Actions\Productive;

use App\Models\ProductiveDocumentType;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductiveAttachment;
use Illuminate\Console\Command;
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
     * Execute the action to store a document type from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "document_types",
     *     "attributes": {
     *         "name": string,
     *         "tax1_name": string,
     *         "tax1_value": string,
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
        $documentTypeData = $parameters['documentTypeData'] ?? null;
        $command = $parameters['command'] ?? null;

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
            'type' => $documentTypeData['type'] ?? 'document_types',
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

        try {
            ProductiveDocumentType::updateOrCreate(
                ['id' => $documentTypeData['id']],
                $data
            );

            if ($command) {
                $command->info("Stored document type '{$attributes['name']}' (ID: {$documentTypeData['id']})");
            }
        } catch (\Exception $e) {
            if ($command) {
                $command->error("Failed to store document type '{$attributes['name']}' (ID: {$documentTypeData['id']})");
                $command->error("Error: " . $e->getMessage());
                $command->warn("Document type data: " . json_encode($data));
            }
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
        foreach ($this->foreignKeys as $key => $modelClass) {
            if (isset($relationships[$key]['data']['id'])) {
                $id = $relationships[$key]['data']['id'];
                if (!$modelClass::where('id', $id)->exists()) {
                    if ($command) {
                        $command->warn("Document type '{$documentTypeName}' is linked to {$key}: {$id}, but this record doesn't exist in our database.");
                    }
                    $data[$key] = null;
                } else {
                    $data[$key] = $id;
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
            'organization_id' => 'nullable|string',
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
