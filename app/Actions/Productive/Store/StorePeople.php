<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveCompany;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveTeam;
use App\Models\ProductiveApa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StorePeople extends AbstractAction
{
    /**
     * Required fields that must be present in the people data
     */
    protected array $requiredFields = [
        'type',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'manager_id' => ProductivePeople::class,
        'company_id' => ProductiveCompany::class,
        'subsidiary_id' => ProductiveSubsidiary::class,
        'apa_id' => ProductiveApa::class,
        'team_id' => ProductiveTeam::class
    ];

    /**
     * Store a person in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $personData = $parameters['personData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$personData) {
            throw new \Exception('Person data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing person: {$personData['id']}");
            }

            // Validate basic data structure
            if (!isset($personData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $personData['attributes'] ?? [];
            $relationships = $personData['relationships'] ?? [];

            // Debug log relationships
            // if ($command instanceof Command) {
            //     $command->info("Person relationships: " . json_encode($relationships));
            // }

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($personData['type'])) {
                $attributes['type'] = $personData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $personData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $personData['id'],
                'type' => $attributes['type'] ?? $personData['type'] ?? 'people',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle JSON fields
            $this->handleJsonFields($data);

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, "{$attributes['first_name']} {$attributes['last_name']}", $command);

            // Debug log final data
            // if ($command instanceof Command) {
            //     $command->info("Final person data: " . json_encode($data));
            // }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update person
            ProductivePeople::updateOrCreate(
                ['id' => $personData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored person: {$attributes['first_name']} {$attributes['last_name']} (ID: {$personData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store person {$personData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store person {$personData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $personId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $personId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for person {$personId}: " . implode(', ', $missingFields);
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
            'contact',
            'tag_list',
            'custom_fields',
            'availabilities',
            'custom_field_people',
            'custom_field_attachments'
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
     * @param string $personName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $personName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'manager' => 'manager_id',
            'company' => 'company_id',
            'subsidiary' => 'subsidiary_id',
            'approval_policy_assignment' => 'apa_id',
            'teams' => 'team_id'
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
                        $command->warn("Person '{$personName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'avatar_url' => 'nullable|string',
            'contact' => 'nullable|json',
            'deactivated_at' => 'nullable|date',
            'email' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'nickname' => 'nullable|string',
            'original_avatar_url' => 'nullable|string',
            'role_id' => 'nullable|integer',
            'status_emoji' => 'nullable|string',
            'status_expires_at' => 'nullable|date',
            'status_text' => 'nullable|string',
            'time_off_status_sync' => 'boolean',
            'title' => 'nullable|string',
            'archived_at' => 'nullable|date',
            'autotracking' => 'boolean',
            'joined_at' => 'nullable|date',
            'last_seen_at' => 'nullable|date',
            'invited_at' => 'nullable|date',
            'is_user' => 'boolean',
            'user_id' => 'nullable|integer',
            'tag_list' => 'nullable|json',
            'virtual' => 'boolean',
            'custom_fields' => 'nullable|json',
            'created_at_api' => 'nullable|date',
            'placeholder' => 'boolean',
            'color_id' => 'nullable|integer',
            'sample_data' => 'boolean',
            'time_unlocked' => 'boolean',
            'time_unlocked_on' => 'nullable|date',
            'time_unlocked_start_date' => 'nullable|date',
            'time_unlocked_end_date' => 'nullable|date',
            'time_unlocked_period_id' => 'nullable|integer',
            'time_unlocked_interval' => 'nullable|integer',
            'last_activity_at' => 'nullable|date',
            'two_factor_auth' => 'nullable|boolean',
            'availabilities' => 'nullable|json',
            'external_id' => 'nullable|string',
            'external_sync' => 'boolean',
            'hrm_type_id' => 'nullable|integer',
            'champion' => 'boolean',
            'timesheet_submission_disabled' => 'boolean',
            'manager_id' => 'nullable|string',
            'company_id' => 'nullable|string',
            'subsidiary_id' => 'nullable|string',
            'apa_id' => 'nullable|string',
            'team_id' => 'nullable|string',
            'custom_field_people' => 'nullable|json',
            'custom_field_attachments' => 'nullable|json'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
