<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveTaxRate;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveOrganization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTaxRate extends AbstractAction
{
    /**
     * Required fields that must be present in the tax rate data
     */
    protected array $requiredFields = [
        'name'
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'subsidiary_id' => ProductiveSubsidiary::class,
    ];

    /**
     * Store a tax rate in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $taxRateData = $parameters['taxRateData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$taxRateData) {
            throw new \Exception('Tax rate data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing tax rate: {$taxRateData['id']}");
            }

            // Validate basic data structure
            if (!isset($taxRateData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $taxRateData['attributes'] ?? [];
            $relationships = $taxRateData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($taxRateData['type'])) {
                $attributes['type'] = $taxRateData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $taxRateData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $taxRateData['id'],
                'type' => $attributes['type'] ?? $taxRateData['type'] ?? 'tax_rates',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Tax Rate', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update tax rate
            ProductiveTaxRate::updateOrCreate(
                ['id' => $taxRateData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored tax rate: {$attributes['name']} (ID: {$taxRateData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store tax rate {$taxRateData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store tax rate {$taxRateData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $taxRateId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $taxRateId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for tax rate {$taxRateId}: " . implode(', ', $missingFields);
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
     * @param string $taxRateName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $taxRateName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'subsidiary' => 'subsidiary_id'
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
                        $command->warn("Tax rate '{$taxRateName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'primary_component_name' => 'required|string',
            'primary_component_value' => 'numeric|min:0|max:100',
            'secondary_component_name' => 'nullable|string',
            'secondary_component_value' => 'nullable|numeric|min:0|max:100',
            
            'subsidiary_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
