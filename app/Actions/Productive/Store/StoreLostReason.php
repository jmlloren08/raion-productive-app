<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveCompany;
use App\Models\ProductiveLostReason;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreLostReason extends AbstractAction
{
    /**
     * Required fields that must be present in the lost reason data
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'company_id' => ProductiveCompany::class
    ];

    /**
     * Store a lost reason in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $lostReasonData = $parameters['lostReasonData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$lostReasonData) {
            throw new \Exception('Lost reason data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing lost reason: {$lostReasonData['id']}");
            }

            // Validate basic data structure
            if (!isset($lostReasonData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $lostReasonData['attributes'] ?? [];
            $relationships = $lostReasonData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($lostReasonData['type'])) {
                $attributes['type'] = $lostReasonData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $lostReasonData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $lostReasonData['id'],
                'type' => $attributes['type'] ?? $lostReasonData['type'] ?? 'lost_reasons',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update lost reason
            ProductiveLostReason::updateOrCreate(
                ['id' => $lostReasonData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored lost reason: {$attributes['name']} (ID: {$lostReasonData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store lost reason {$lostReasonData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store lost reason {$lostReasonData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $lostReasonId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $lostReasonId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for lost reason {$lostReasonId}: " . implode(', ', $missingFields);
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
            'name' => 'required|string',
            'archived_at' => 'nullable|date',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 