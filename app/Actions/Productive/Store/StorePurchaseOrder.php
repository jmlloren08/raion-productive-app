<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveCompany;
use App\Models\ProductivePurchaseOrder;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveDeal;
use App\Models\ProductiveDocumentType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StorePurchaseOrder extends AbstractAction
{
    /**
     * Required fields that must be present in the purchase order data
     */
    protected array $requiredFields = [
        // no required fields / all nullable  
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'deal_id' => ProductiveDeal::class,
        'creator_id' => ProductiveContactEntry::class,
        'document_type_id' => ProductiveDocumentType::class,
        'attachment_id' => ProductiveAttachment::class,
        'bill_to_id' => ProductiveCompany::class,
        'bill_from_id' => ProductiveCompany::class
    ];

    /**
     * Store a purchase order in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $purchaseOrderData = $parameters['purchaseOrderData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$purchaseOrderData) {
            throw new \Exception('Purchase order data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing purchase order: {$purchaseOrderData['id']}");
            }

            // Validate basic data structure
            if (!isset($purchaseOrderData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $purchaseOrderData['attributes'] ?? [];
            $relationships = $purchaseOrderData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($purchaseOrderData['type'])) {
                $attributes['type'] = $purchaseOrderData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $purchaseOrderData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $purchaseOrderData['id'],
                'type' => $attributes['type'] ?? $purchaseOrderData['type'] ?? 'purchase_orders',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['number'] ?? 'Unknown PO', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update purchase order
            ProductivePurchaseOrder::updateOrCreate(
                ['id' => $purchaseOrderData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored purchase order: {$attributes['number']} (ID: {$purchaseOrderData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store purchase order {$purchaseOrderData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store purchase order {$purchaseOrderData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $purchaseOrderId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $purchaseOrderId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for purchase order {$purchaseOrderId}: " . implode(', ', $missingFields);
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
            'vendor',
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
     * @param string $purchaseOrderNumber
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $purchaseOrderNumber, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'deal' => 'deal_id',
            'creator' => 'creator_id',
            'document_type' => 'document_type_id',
            'attachment' => 'attachment_id',
            'bill_to' => 'bill_to_id',
            'bill_from' => 'bill_from_id'
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
                        $command->warn("Purchase Order '{$purchaseOrderNumber}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'subject' => 'nullable|string',
            'status_id' => 'nullable|integer',
            'issued_on' => 'nullable|date',
            'delivery_on' => 'nullable|date',
            'sent_on' => 'nullable|date',
            'received_on' => 'nullable|date',
            'created_at_api' => 'nullable|date',
            'number' => 'nullable|string',
            'note' => 'nullable|string',
            'note_interpolated' => 'nullable|string',
            'email_key' => 'nullable|string',
            'payment_status_id' => 'nullable|integer',
            'exchange_rate' => 'nullable|numeric',
            'exchange_date' => 'nullable|date',
            'currency' => 'nullable|string',
            'currency_default' => 'nullable|string',
            'currency_normalized' => 'nullable|string',
            'total_cost' => 'nullable|numeric',
            'total_cost_default' => 'nullable|numeric',
            'total_cost_normalized' => 'nullable|numeric',
            'total_cost_with_tax' => 'nullable|numeric',
            'total_cost_with_tax_default' => 'nullable|numeric',
            'total_cost_with_tax_normalized' => 'nullable|numeric',
            'total_received' => 'nullable|numeric',
            'total_received_default' => 'nullable|numeric',
            'total_received_normalized' => 'nullable|numeric',
            // Relationships
            'vendor' => 'nullable|json',
            'deal_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'document_type_id' => 'nullable|string',
            'attachment_id' => 'nullable|string',
            'bill_to_id' => 'nullable|string',
            'bill_from_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
