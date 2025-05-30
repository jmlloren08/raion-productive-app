<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveCompany;
use App\Models\ProductiveInvoice;
use App\Models\ProductivePeople;
use App\Models\ProductiveDeal;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveTaxRate;
use App\Models\ProductiveDocumentType;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductiveAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreInvoice extends AbstractAction
{
    /**
     * Required fields that must be present in the invoice data
     */
    protected array $requiredFields = [
        'number',
        'status'
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'company_id' => ProductiveCompany::class,
        'creator_id' => ProductivePeople::class,
        'deal_id' => ProductiveDeal::class,
        'contact_entry_id' => ProductiveContactEntry::class,
        'subsidiary_id' => ProductiveSubsidiary::class,
        'tax_rate_id' => ProductiveTaxRate::class,
        'document_type_id' => ProductiveDocumentType::class,
        'document_style_id' => ProductiveDocumentStyle::class,
        'attachment_id' => ProductiveAttachment::class
    ];

    /**
     * Store an invoice in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $invoiceData = $parameters['invoiceData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$invoiceData) {
            throw new \Exception('Invoice data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing invoice: {$invoiceData['id']}");
            }

            // Validate basic data structure
            if (!isset($invoiceData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $invoiceData['attributes'] ?? [];
            $relationships = $invoiceData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($invoiceData['type'])) {
                $attributes['type'] = $invoiceData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $invoiceData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $invoiceData['id'],
                'type' => $attributes['type'] ?? $invoiceData['type'] ?? 'invoices',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['number'] ?? 'Unknown Invoice', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update invoice
            ProductiveInvoice::updateOrCreate(
                ['id' => $invoiceData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored invoice: {$attributes['number']} (ID: {$invoiceData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store invoice {$invoiceData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store invoice {$invoiceData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $invoiceId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $invoiceId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for invoice {$invoiceId}: " . implode(', ', $missingFields);
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
            'custom_fields',
            'line_items',
            'totals',
            'payment_terms',
            'payment_reminder_sequence'
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
     * @param string $invoiceNumber
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $invoiceNumber, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'company' => 'company_id',
            'creator' => 'creator_id',
            'deal' => 'deal_id',
            'contact_entry' => 'contact_entry_id',
            'subsidiary' => 'subsidiary_id',
            'tax_rate' => 'tax_rate_id',
            'document_type' => 'document_type_id',
            'document_style' => 'document_style_id',
            'attachment' => 'attachment_id'
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
                        $command->warn("Invoice '{$invoiceNumber}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'number' => 'required|string',
            'status' => 'required|string',
            'type' => 'required|string',
            'created_at' => 'required|date',
            'updated_at' => 'required|date',
            'due_date' => 'nullable|date',
            'issue_date' => 'nullable|date',
            'paid_at' => 'nullable|date',
            'currency' => 'required|string',
            'exchange_rate' => 'nullable|numeric',
            'total_amount' => 'required|numeric',
            'total_amount_in_organization_currency' => 'required|numeric',
            'preferences' => 'nullable|json',
            'custom_fields' => 'nullable|json',
            'line_items' => 'nullable|json',
            'totals' => 'nullable|json',
            'payment_terms' => 'nullable|json',
            'payment_reminder_sequence' => 'nullable|json',
            
            'company_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'contact_entry_id' => 'nullable|string',
            'subsidiary_id' => 'nullable|string',
            'tax_rate_id' => 'nullable|string',
            'document_type_id' => 'nullable|string',
            'document_style_id' => 'nullable|string',
            'attachment_id' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 