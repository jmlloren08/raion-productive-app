<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveDeal;
use App\Models\ProductiveInvoice;
use App\Models\ProductiveInvoiceAttribution;
use App\Models\ProductivePeople;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreInvoiceAttribution extends AbstractAction
{
    /**
     * Required fields that must be present in the invoice attribution data
     */
    protected array $requiredFields = [
        'amount',
        'amount_default',
        'amount_normalized',
        'currency',
        'currency_default',
        'currency_normalized',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'invoice_id' => ProductiveInvoice::class,
        'budget_id' => ProductiveDeal::class,
    ];

    /**
     * Store an invoice attribution in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $invoiceAttributionData = $parameters['invoiceAttributionData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$invoiceAttributionData) {
            throw new \Exception('Invoice attribution data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing invoice attribution: {$invoiceAttributionData['id']}");
            }

            // Validate basic data structure
            if (!isset($invoiceAttributionData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $invoiceAttributionData['attributes'] ?? [];
            $relationships = $invoiceAttributionData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($invoiceAttributionData['type'])) {
                $attributes['type'] = $invoiceAttributionData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $invoiceAttributionData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $invoiceAttributionData['id'],
                'type' => $attributes['type'] ?? $invoiceAttributionData['type'] ?? 'invoice_attributions',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['date_from'] ?? 'Unknown Invoice Attribution', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update invoice attribution
            ProductiveInvoiceAttribution::updateOrCreate(
                ['id' => $invoiceAttributionData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored invoice attribution: {$attributes['date_from']} (ID: {$invoiceAttributionData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store invoice attribution {$invoiceAttributionData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store invoice attribution {$invoiceAttributionData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $invoiceAttributionId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $invoiceAttributionId, ?Command $command): void
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
            $message = "Required fields missing for invoice attribution {$invoiceAttributionId}: " . implode(', ', $missingFields);
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
     * @param string $invoiceAttributionId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $invoiceAttributionId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'invoice' => 'invoice_id',
            'budget' => 'budget_id',
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
                        $command->warn("Invoice attribution '{$invoiceAttributionId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'amount' => 'required|integer',
            'amount_default' => 'required|integer',
            'amount_normalized' => 'required|integer',
            'currency' => 'required|string|max:3',
            'currency_default' => 'required|string|max:3',
            'currency_normalized' => 'required|string|max:3',
            // Foreign key relationships
            'invoice_id' => 'nullable|string',
            'budget_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 