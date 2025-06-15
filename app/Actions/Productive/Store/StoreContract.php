<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveCompany;
use App\Models\ProductiveContract;
use App\Models\ProductiveDeal;
use App\Models\ProductiveDocumentType;
use App\Models\ProductivePeople;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveTaxRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreContract extends AbstractAction
{
    /**
     * Required fields that must be present in the contract data
     */
    protected array $requiredFields = [
        'copy_purchase_order_number',
        'copy_expenses',
        'use_rollover_hours',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'deal_id' => ProductiveDeal::class,
    ];

    /**
     * Store a contract in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $contractData = $parameters['contractData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$contractData) {
            throw new \Exception('Contract data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing contract: {$contractData['id']}");
            }

            // Validate basic data structure
            if (!isset($contractData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $contractData['attributes'] ?? [];
            $relationships = $contractData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($contractData['type'])) {
                $attributes['type'] = $contractData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $contractData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $contractData['id'],
                'type' => $attributes['type'] ?? $contractData['type'] ?? 'contracts',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, "{$attributes['copy_purchase_order_number']} {$attributes['copy_expenses']} {$attributes['use_rollover_hours']}" ?? 'Unknown Contract', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update contract
            ProductiveContract::updateOrCreate(
                ['id' => $contractData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored contract: {$attributes['copy_purchase_order_number']} {$attributes['copy_expenses']} {$attributes['use_rollover_hours']} (ID: {$contractData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store contract {$contractData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store contract {$contractData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $contractId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $contractId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for contract {$contractId}: " . implode(', ', $missingFields);
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
     * @param string $contractName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $contractName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'deal' => 'deal_id',
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
                        $command->warn("Contract '{$contractName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'ends_on' => 'nullable|date',
            'starts_on' => 'nullable|date',
            'next_occurrence_on' => 'nullable|date',
            'interval_id' => 'nullable|integer',
            'copy_purchase_order_number' => 'boolean',
            'copy_expenses' => 'boolean',
            'use_rollover_hours' => 'boolean',

            'deal_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
