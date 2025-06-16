<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreEvent extends AbstractAction
{
    /**
     * Required fields that must be present in the event data
     */
    protected array $requiredFields = [
        'name',
        'event_type_id',
        'sync_personal_integrations',
        'half_day_bookings'
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        // No foreign keys defined for events
    ];

    /**
     * Store an event in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $eventData = $parameters['eventData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$eventData) {
            throw new \Exception('Event data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing event: {$eventData['id']}");
            }

            // Validate basic data structure
            if (!isset($eventData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $eventData['attributes'] ?? [];
            $relationships = $eventData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($eventData['type'])) {
                $attributes['type'] = $eventData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $eventData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $eventData['id'],
                'type' => $attributes['type'] ?? $eventData['type'] ?? 'events',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update event
            ProductiveEvent::updateOrCreate(
                ['id' => $eventData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored event: {$attributes['name']} (ID: {$eventData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store event {$eventData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store event {$eventData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $eventId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $eventId, ?Command $command): void
    {
        // Check for missing required fields
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for event {$eventId}: " . implode(', ', $missingFields);
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
            'event_type_id' => 'required|integer',
            'sync_personal_integrations' => 'required|boolean',
            'half_day_bookings' => 'required|boolean',
            'icon_id' => 'nullable|string',
            'color_id' => 'nullable|string',
            'archived_at' => 'nullable|date',
            'limitation_type_id' => 'required|integer',
            'description' => 'nullable|string',
            'absence_type' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
} 