<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductivePeople;
use App\Models\ProductiveTeam;
use App\Models\ProductiveCompany;
use App\Models\ProductiveSubsidiary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTeam extends AbstractAction
{
    /**
     * Required fields that must be present in the team data
     */
    protected array $requiredFields = [
        'name',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        '',
    ];

    /**
     * Store a team in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $teamData = $parameters['teamData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$teamData) {
            throw new \Exception('Team data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing team: {$teamData['id']}");
            }

            // Validate basic data structure
            if (!isset($teamData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $teamData['attributes'] ?? [];
            $relationships = $teamData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($teamData['type'])) {
                $attributes['type'] = $teamData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $teamData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $teamData['id'],
                'type' => $attributes['type'] ?? $teamData['type'] ?? 'teams',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update team
            ProductiveTeam::updateOrCreate(
                ['id' => $teamData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored team: {$attributes['name']} (ID: {$teamData['id']})");
            }

            return true;

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store team {$teamData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store team {$teamData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $teamId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $teamId, ?Command $command): void
    {
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for team {$teamId}: " . implode(', ', $missingFields);
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
            'color_id' => 'nullable|string',
            'icon_id' => 'nullable|string',
            'name' => 'required|string',
            'members_included' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}