<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveBill;
use App\Models\ProductiveCompany;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveTaxRate;
use App\Models\ProductiveDocumentType;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveDeal;
use App\Models\ProductivePurchaseOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreBill extends AbstractAction
{
    /**
     * Required fields that must be present in the bill data
     */
    protected array $requiredFields = [
        '',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'purchase_order_id' => ProductivePurchaseOrder::class,
        'creator_id' => ProductivePeople::class,
        'deal_id' => ProductiveDeal::class,
        'attachment_id' => ProductiveAttachment::class,
    ];

    /**
     * Store a bill in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $billData = $parameters['billData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$billData) {
            throw new \Exception('Bill data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing bill: {$billData['id']}");
            }

            // Validate basic data structure
            if (!isset($billData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $billData['attributes'] ?? [];
            $relationships = $billData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($billData['type'])) {
                $attributes['type'] = $billData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $billData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $billData['id'],
                'type' => $attributes['type'] ?? $billData['type'] ?? 'bills',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['subject'] ?? 'Unknown Bill', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update bill
            ProductiveBill::updateOrCreate(
                ['id' => $billData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored bill: {$attributes['subject']} (ID: {$billData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store bill {$billData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store bill {$billData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $billId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $billId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for bill {$billId}: " . implode(', ', $missingFields);
            if ($command) {
                $command->error($message);
            }
            throw new \Exception($message);
        }
    }

    /**
     * Handle foreign key relationships
     *
     * @param array $relationships
     * @param array &$data
     * @param string $billName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $billName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'purchase_order' => 'purchase_order_id',
            'creator' => 'creator_id',
            'deal' => 'deal_id',
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
                        $command->warn("Bill '{$billName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'invoice_number' => 'nullable|string',
            'description' => 'nullable|string',
            'created_at_api' => 'nullable|date',
            'currency' => 'nullable|string',
            'currency_default' => 'nullable|string',
            'currency_normalized' => 'nullable|string',
            'total_received' => 'nullable|numeric',
            'total_received_default' => 'nullable|numeric',
            'total_received_normalized' => 'nullable|numeric',
            'total_cost' => 'nullable|numeric',
            'total_cost_default' => 'nullable|numeric',
            'total_cost_normalized' => 'nullable|numeric',

            'purchase_order_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'attachment_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 