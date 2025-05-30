<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveApa;
use App\Models\ProductiveApprovalPolicyAssignment;
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

class StoreApprovalPolicyAssignment extends AbstractAction
{
    /**
     * Required fields that must be present in the approval policy assignment data
     */
    protected array $requiredFields = [
        'target_type',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'person_id' => ProductivePeople::class,
        'deal_id' => ProductiveDeal::class,
        'approval_policy_id' => ProductiveApprovalPolicy::class,
    ];

    /**
     * Store an approval policy assignment in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $assignmentData = $parameters['assignmentData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$assignmentData) {
            throw new \Exception('Approval policy assignment data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing approval policy assignment: {$assignmentData['id']}");
            }

            // Validate basic data structure
            if (!isset($assignmentData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $assignmentData['attributes'] ?? [];
            $relationships = $assignmentData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($assignmentData['type'])) {
                $attributes['type'] = $assignmentData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $assignmentData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $assignmentData['id'],
                'type' => $attributes['type'] ?? $assignmentData['type'] ?? 'approval_policy_assignments',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['target_type'] ?? 'Unknown APA', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update approval policy assignment
            ProductiveApa::updateOrCreate(
                ['id' => $assignmentData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored approval policy assignment: {$attributes['target_type']} (ID: {$assignmentData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store approval policy assignment {$assignmentData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store approval policy assignment {$assignmentData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $assignmentId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $assignmentId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for approval policy assignment {$assignmentId}: " . implode(', ', $missingFields);
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
     * @param string $assignmentId
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $assignmentId, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'person' => 'person_id',
            'deal' => 'deal_id',
            'approval_policy' => 'approval_policy_id',
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
                        $command->warn("Approval Policy Assignment '{$assignmentId}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'target_type' => 'required|string',

            'person_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'approval_policy_id' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
