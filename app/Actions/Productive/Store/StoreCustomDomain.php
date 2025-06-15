<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveCustomDomain;
use App\Models\ProductiveSubsidiary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreCustomDomain extends AbstractAction
{
    /**
     * Required fields that must be present in the custom domain data
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'subsidiary_id' => ProductiveSubsidiary::class,
    ];

    /**
     * Store a custom domain in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $customDomainData = $parameters['customDomainData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$customDomainData) {
            throw new \Exception('Custom domain data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing custom domain: {$customDomainData['id']}");
            }

            // Validate basic data structure
            if (!isset($customDomainData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $customDomainData['attributes'] ?? [];
            $relationships = $customDomainData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($customDomainData['type'])) {
                $attributes['type'] = $customDomainData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $customDomainData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $customDomainData['id'],
                'type' => $attributes['type'] ?? $customDomainData['type'] ?? 'custom_domains',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Domain', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update custom domain
            ProductiveCustomDomain::updateOrCreate(
                ['custom_domain_id' => $customDomainData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored custom domain: {$attributes['name']} (ID: {$customDomainData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store custom domain {$customDomainData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store custom domain {$customDomainData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $customDomainId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $customDomainId, ?Command $command): void
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
            $message = "Required fields missing for custom domain {$customDomainId}: " . implode(', ', $missingFields);
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
            'mailgun_mx',
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
     * @param string $domainName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $domainName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'subsidiaries' => ['dbKey' => 'subsidiary_id', 'lookupColumn' => 'subsidiary_id'],
        ];

        foreach ($relationshipMap as $apiKey => $config) {
            if (isset($relationships[$apiKey]['data']['id'])) {
                $id = $relationships[$apiKey]['data']['id'];
                if ($command) {
                    $command->info("Processing relationship {$apiKey} with ID: {$id}");
                }

                // Get the model class for this relationship
                $modelClass = $this->foreignKeys[$config['dbKey']];

                if (!$modelClass::where($config['lookupColumn'], $id)->exists()) {
                    if ($command) {
                        $command->warn("Project '{$domainName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
                    }
                    $data[$config['dbKey']] = null;
                } else {
                    $data[$config['dbKey']] = $id;
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
            'verified_at' => 'nullable|date',
            'email_sender_name' => 'nullable|string',
            'email_sender_address' => 'nullable|string',
            'mailgun_dkim' => 'nullable|string',
            'mailgun_spf' => 'nullable|string',
            'mailgun_mx' => 'nullable|json',
            'allow_user_email' => 'boolean',

            'subsidiary_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
