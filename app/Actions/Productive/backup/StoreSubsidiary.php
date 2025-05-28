<?php

namespace App\Actions\Productive;

use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveCustomDomain;
use App\Models\ProductiveTaxRate;
use App\Models\ProductiveIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Action to store a subsidiary from Productive API data.
 * Expected data structure:
 * {
 *     "data": {
 *         "id": string,
 *         "type": "subsidiaries",
 *         "attributes": {
 *             "name": string,
 *             ...
 *         }
 *     }
 * }
 */
class StoreSubsidiary extends AbstractAction
{
    /**
     * Required fields and their locations in the data structure
     * 'id' is expected in the root data object
     * other fields are expected in the attributes object
     */
    protected array $requiredFields = [
        'name'  // id is validated separately since it's in the root object
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'bill_from_id' => ProductiveContactEntry::class,
        'custom_domain_id' => ProductiveCustomDomain::class,
        'default_tax_rate_id' => ProductiveTaxRate::class,
        'integration_id' => ProductiveIntegration::class
    ];

    /**
     * Execute the action to store a subsidiary.
     *
     * @param array $parameters
     * @return void
     * @throws \Exception
     */    public function handle(array $parameters = []): void
    {
        $subsidiaryData = $parameters['subsidiaryData'] ?? null;
        $command = $parameters['command'] ?? null;

        // Validate basic data structure
        if (!isset($subsidiaryData['id'])) {
            throw new \Exception("Missing required field 'id' in root data object");
        }

        // Extract attributes and relationships
        $attributes = $subsidiaryData['attributes'] ?? [];
        $relationships = $subsidiaryData['relationships'] ?? [];

        // Validate required fields in attributes
        $this->validateRequiredFields($attributes, $subsidiaryData['id'], $command);

        // Prepare base data
        $data = [
            'id' => $subsidiaryData['id'],
            'type' => $subsidiaryData['type'] ?? 'subsidiaries',
        ];

        // Add all attributes with safe fallbacks
        foreach ($attributes as $key => $value) {
            $data[$key] = $value;
        }

        // Handle foreign key relationships
        $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Subsidiary', $command);

        // Validate data types
        $this->validateDataTypes($data);

        try {
            ProductiveSubsidiary::updateOrCreate(
                ['id' => $subsidiaryData['id']],
                $data
            );

            if ($command) {
                $command->info("Stored subsidiary '{$attributes['name']}' (ID: {$subsidiaryData['id']})");
            }
        } catch (\Exception $e) {
            if ($command) {
                $command->error("Failed to store subsidiary '{$attributes['name']}' (ID: {$subsidiaryData['id']})");
                $command->error("Error: " . $e->getMessage());
                $command->warn("Validation data: " . json_encode($data));
            }
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $subsidiaryId
     * @param Command|null $command
     * @throws \Exception
     */    protected function validateRequiredFields(array $attributes, string $subsidiaryId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing in attributes for subsidiary {$subsidiaryId}: " . implode(', ', $missingFields);
            if ($command) {
                $command->error($message);
            }
            throw new \Exception($message);
        }
    }

    /**
     * Handle foreign key relationships
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $subsidiaryName, ?Command $command): void
    {
        foreach ($this->foreignKeys as $key => $modelClass) {
            if (isset($relationships[$key]['data']['id'])) {
                $id = $relationships[$key]['data']['id'];
                if (!$modelClass::where('id', $id)->exists()) {
                    if ($command) {
                        $command->warn("Subsidiary '{$subsidiaryName}' is linked to {$key}: {$id}, but this record doesn't exist in our database.");
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
     */
    protected function validateDataTypes(array $data): void
    {
        $rules = [
            'name' => 'required|string',
            'invoice_number_format' => 'nullable|string',
            'invoice_number_scope' => 'nullable|string',
            'archived_at' => 'nullable|date',
            'show_delivery_date' => 'boolean',
            'einvoice_payment_means_type_id' => 'nullable|integer',
            'einvoice_download_format_id' => 'nullable|integer',
            'peppol_id' => 'nullable|string',
            'export_integration_type_id' => 'nullable|integer',
            'invoice_logo_url' => 'nullable|string',
            // Foreign keys
            'bill_from_id' => 'nullable|string',
            'custom_domain_id' => 'nullable|string',
            'default_tax_rate_id' => 'nullable|string',
            'integration_id' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
