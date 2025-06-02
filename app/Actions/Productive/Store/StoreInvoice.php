<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveCompany;
use App\Models\ProductiveInvoice;
use App\Models\ProductivePeople;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveDocumentType;
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
        // No required fields / all nullable
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'bill_to_id' => ProductiveContactEntry::class,
        'bill_from_id' => ProductiveContactEntry::class,
        'company_id' => ProductiveCompany::class,
        'document_type_id' => ProductiveDocumentType::class,
        'creator_id' => ProductivePeople::class,
        'subsidiary_id' => ProductiveSubsidiary::class,
        'parent_invoice_id' => ProductiveInvoice::class,
        'issuer_id' => ProductivePeople::class,
        'invoice_attribution_id' => ProductiveInvoice::class,
        'attachment_id' => ProductiveAttachment::class,
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
        $arrayFields = [
            'tag_list',
            'custom_fields',
            'creation_options',
            'custom_field_people',
            'custom_field_attachments',
        ];

        foreach ($arrayFields as $field) {
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
            'bill_to' => 'bill_to_id',
            'bill_from' => 'bill_from_id',
            'company' => 'company_id',
            'document_type' => 'document_type_id',
            'creator' => 'creator_id',
            'subsidiary' => 'subsidiary_id',
            'parent_invoice' => 'parent_invoice_id',
            'issuer' => 'issuer_id',
            'invoice_attributions' => 'invoice_attribution_id',
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
            'number' => 'nullable|string',
            'subject' => 'nullable|string',
            'invoiced_on' => 'nullable|date',
            'sent_on' => 'nullable|date',
            'pay_on' => 'nullable|date',
            'delivery_on' => 'nullable|date',
            'paid_on' => 'nullable|date',
            'finalized_on' => 'nullable|date',
            'discount' => 'nullable|numeric',
            'tax1_name' => 'nullable|string',
            'tax1_value' => 'nullable|numeric',
            'tax2_name' => 'nullable|string',
            'tax2_value' => 'nullable|numeric',
            'deleted_at_api' => 'nullable|date',
            'note' => 'nullable|string',
            'exported' => 'nullable|boolean',
            'exported_at' => 'nullable|date',
            'export_integration_type_id' => 'nullable|integer',
            'export_id' => 'nullable|string',
            'export_invoice_url' => 'nullable|string',
            'company_reference_id' => 'nullable|string',
            'note_interpolated' => 'nullable|string',
            'email_key' => 'nullable|string',
            'purchase_order_number' => 'nullable|string',
            'created_at_api' => 'nullable|date',
            'exchange_rate' => 'nullable|numeric',
            'exchange_date' => 'nullable|date',
            'updated_at_api' => 'nullable|date',
            'sample_data' => 'nullable|boolean',
            'pay_on_relative' => 'nullable|boolean',
            'invoice_type_id' => 'nullable|integer',
            'credited' => 'nullable|boolean',
            'line_item_tax' => 'nullable|boolean',
            'last_activity_at' => 'nullable|date',
            'payment_terms' => 'nullable|integer',
            'currency' => 'nullable|string',
            'currency_default' => 'nullable|string',
            'currency_normalized' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'amount_default' => 'nullable|numeric',
            'amount_normalized' => 'nullable|numeric',
            'amount_tax' => 'nullable|numeric',
            'amount_tax_default' => 'nullable|numeric',
            'amount_tax_normalized' => 'nullable|numeric',
            'amount_with_tax' => 'nullable|numeric',
            'amount_with_tax_default' => 'nullable|numeric',
            'amount_with_tax_normalized' => 'nullable|numeric',
            'amount_paid' => 'nullable|numeric',
            'amount_paid_default' => 'nullable|numeric',
            'amount_paid_normalized' => 'nullable|numeric',
            'amount_written_off' => 'nullable|numeric',
            'amount_written_off_default' => 'nullable|numeric',
            'amount_written_off_normalized' => 'nullable|numeric',
            'amount_unpaid' => 'nullable|numeric',
            'amount_unpaid_default' => 'nullable|numeric',
            'amount_unpaid_normalized' => 'nullable|numeric',
            'amount_credited' => 'nullable|numeric',
            'amount_credited_default' => 'nullable|numeric',
            'amount_credited_normalized' => 'nullable|numeric',
            'amount_credited_with_tax' => 'nullable|numeric',
            'amount_credited_with_tax_default' => 'nullable|numeric',
            'amount_credited_with_tax_normalized' => 'nullable|numeric',
            // Relationships
            'bill_to_id' => 'nullable|string',
            'bill_from_id' => 'nullable|string',
            'company_id' => 'nullable|string',
            'document_type_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'subsidiary_id' => 'nullable|string',
            'parent_invoice_id' => 'nullable|string',
            'issuer_id' => 'nullable|string',
            'invoice_attribution_id' => 'nullable|string',
            'attachment_id' => 'nullable|string',
            // JSON fields - allow both array and string (for JSON)
            'tag_list' => 'nullable|string',
            'custom_fields' => 'nullable|string',
            'creation_options' => 'nullable|string',
            'custom_field_people' => 'nullable|string',
            'custom_field_attachments' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 