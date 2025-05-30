<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveApprovalPolicy;
use App\Models\ProductiveCompany;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductivePeople;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreApprovalPolicy extends AbstractAction
{
    /**
     * Required fields that must be present in the approval policy data
     */
    protected array $requiredFields = [
        'id',
        'type'
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        '',
    ];

    /**
     * Store an approval policy in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $policyData = $parameters['policyData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$policyData) {
            throw new \Exception('Approval policy data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing approval policy: {$policyData['id']}");
            }

            // Validate basic data structure
            if (!isset($policyData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $policyData['attributes'] ?? [];
            $relationships = $policyData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($policyData['type'])) {
                $attributes['type'] = $policyData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $policyData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $policyData['id'],
                'type' => $attributes['type'] ?? $policyData['type'] ?? 'approval_policies',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update approval policy
            ProductiveApprovalPolicy::updateOrCreate(
                ['id' => $policyData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored approval policy: {$attributes['name']} (ID: {$policyData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store approval policy {$policyData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store approval policy {$policyData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $policyId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $policyId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for approval policy {$policyId}: " . implode(', ', $missingFields);
            if ($command) {
                $command->error($message);
            }
            throw new \Exception($message);
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
            'id' => 'required|string',
            'type' => 'required|string',
            'archived_at' => 'nullable|date',
            'custom' => 'boolean',
            'default' => 'boolean',
            'description' => 'nullable|string',
            'name' => 'required|string',
            'type_id' => 'nullable|integer',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 