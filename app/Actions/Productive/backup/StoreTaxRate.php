<?php

namespace App\Actions\Productive;

use App\Models\ProductiveTaxRate;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveOrganization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTaxRate extends AbstractAction
{
    /**
     * Required fields that must be present in the tax rate attributes
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
     * Execute the action to store a tax rate from Productive API data.
     * Expected data structure:
     * {
     *     "id": string,
     *     "type": "tax_rates",
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
        $taxRateData = $parameters['taxRateData'] ?? null;
        $command = $parameters['command'] ?? null;

        // Validate basic data structure
        if (!isset($taxRateData['id'])) {
            throw new \Exception("Missing required field 'id' in root data object");
        }

        $attributes = $taxRateData['attributes'] ?? [];
        $relationships = $taxRateData['relationships'] ?? [];

        // Validate required fields in attributes
        $this->validateRequiredFields($attributes, $taxRateData['id'], $command);

        // Prepare base data
        $data = [
            'id' => $taxRateData['id'],
            'type' => $taxRateData['type'] ?? 'tax_rates',
        ];

        // Add all attributes with safe fallbacks
        foreach ($attributes as $key => $value) {
            $data[$key] = $value;
        }

        // Handle foreign key relationships
        $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Tax Rate', $command);

        // Validate data types
        $this->validateDataTypes($data);

        try {
            ProductiveTaxRate::updateOrCreate(
                ['id' => $taxRateData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored tax rate: {$data['name']}");
            }
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store tax rate {$taxRateData['id']}: " . $e->getMessage());
                $command->warn("Validation errors: " . json_encode($data));
            }
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
        foreach ($this->foreignKeys as $key => $modelClass) {
            if (isset($relationships[$key]['data']['id'])) {
                $id = $relationships[$key]['data']['id'];
                if (!$modelClass::where('id', $id)->exists()) {
                    if ($command) {
                        $command->warn("Tax rate '{$taxRateName}' is linked to {$key}: {$id}, but this record doesn't exist in our database.");
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
