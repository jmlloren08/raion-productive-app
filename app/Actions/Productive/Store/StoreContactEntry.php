<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveCompany;
use App\Models\ProductiveContactEntry;
use App\Models\ProductivePeople;
use App\Models\ProductiveInvoice;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductivePurchaseOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreContactEntry extends AbstractAction
{
    /**
     * Required fields that must be present in the contact entry data
     */
    protected array $requiredFields = [
        'contactable_type',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'company_id' => ProductiveCompany::class,
        'person_id' => ProductivePeople::class,
        'invoice_id' => ProductiveInvoice::class,
        'subsidiary_id' => ProductiveSubsidiary::class,
        'purchase_order_id' => ProductivePurchaseOrder::class
    ];

    /**
     * Store a contact entry in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $contactEntryData = $parameters['contactEntryData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$contactEntryData) {
            throw new \Exception('Contact entry data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing contact entry: {$contactEntryData['id']}");
            }

            // Validate basic data structure
            if (!isset($contactEntryData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $contactEntryData['attributes'] ?? [];
            $relationships = $contactEntryData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($contactEntryData['type'])) {
                $attributes['type'] = $contactEntryData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $contactEntryData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $contactEntryData['id'],
                'type_name' => $attributes['type'] ?? $contactEntryData['type'] ?? 'contact_entries',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Contact Entry', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update contact entry
            ProductiveContactEntry::updateOrCreate(
                ['id' => $contactEntryData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored contact entry: {$attributes['name']} (ID: {$contactEntryData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store contact entry {$contactEntryData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store contact entry {$contactEntryData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $contactEntryId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $contactEntryId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for contact entry {$contactEntryId}: " . implode(', ', $missingFields);
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
     * @param string $contactEntryName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $contactEntryName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'company' => 'company_id',
            'person' => 'person_id',
            'invoice' => 'invoice_id',
            'subsidiary' => 'subsidiary_id',
            'purchase_order' => 'purchase_order_id'
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
                        $command->warn("Contact entry '{$contactEntryName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'contactable_type' => 'required|string',
            'type_name' => 'required|string',
            'name' => 'required|string',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
            'website' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zipcode' => 'nullable|string',
            'country' => 'nullable|string',
            'vat' => 'nullable|string',
            'billing_address' => 'nullable|boolean',
            'company_id' => 'nullable|string',
            'person_id' => 'nullable|string',
            'invoice_id' => 'nullable|string',
            'subsidiary_id' => 'nullable|string',
            'purchase_order_id' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
