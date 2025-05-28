<?php

namespace App\Actions\Productive;

use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveCustomDomain;
use App\Models\ProductiveTaxRate;
use App\Models\ProductiveIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
     * Required fields that must be present in the subsidiary data
     */
    protected array $requiredFields = [
        'name'
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'contact_entry_id' => ProductiveContactEntry::class,
        'custom_domain_id' => ProductiveCustomDomain::class,
        'default_tax_rate_id' => ProductiveTaxRate::class,
        'integration_id' => ProductiveIntegration::class
    ];

    /**
     * Store a subsidiary from Productive API data
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $subsidiaryData = $parameters['subsidiaryData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$subsidiaryData) {
            throw new \Exception('Subsidiary data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing subsidiary: {$subsidiaryData['id']}");
            }

            // Validate basic data structure
            if (!isset($subsidiaryData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $subsidiaryData['attributes'] ?? [];
            $relationships = $subsidiaryData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($subsidiaryData['type'])) {
                $attributes['type'] = $subsidiaryData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $subsidiaryData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $subsidiaryData['id'],
                'type' => $attributes['type'] ?? $subsidiaryData['type'] ?? 'subsidiaries',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Subsidiary', $command);

            // Debug log final data
            if ($command instanceof Command) {
                $command->info("Final subsidiary data: " . json_encode($data));
            }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update subsidiary
            ProductiveSubsidiary::updateOrCreate(
                ['id' => $subsidiaryData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored subsidiary: {$attributes['name']} (ID: {$subsidiaryData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store subsidiary {$subsidiaryData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store subsidiary {$subsidiaryData['id']}: " . $e->getMessage());
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
     */
    protected function validateRequiredFields(array $attributes, string $subsidiaryId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for subsidiary {$subsidiaryId}: " . implode(', ', $missingFields);
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
     * @param string $subsidiaryName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $subsidiaryName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'bill_from' => 'contact_entry_id',
            'custom_domain' => 'custom_domain_id',
            'default_tax_rate' => 'default_tax_rate_id',
            'integration' => 'integration_id'
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
                        $command->warn("Subsidiary '{$subsidiaryName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'invoice_number_format' => 'nullable|string',
            'invoice_number_scope' => 'nullable|string',
            'show_delivery_date' => 'nullable|boolean',
            'einvoice_payment_means_type_id' => 'nullable|string',
            'einvoice_download_format_id' => 'nullable|string',
            'peppol_id' => 'nullable|string',
            'export_integration_type_id' => 'nullable|string',
            'invoice_logo_url' => 'nullable|text',

            'contact_entry_id' => 'nullable|string',
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
