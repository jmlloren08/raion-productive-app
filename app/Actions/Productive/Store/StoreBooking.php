<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveApprovalStatus;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveBooking;
use App\Models\ProductiveEvent;
use App\Models\ProductivePeople;
use App\Models\ProductiveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreBooking extends AbstractAction
{
    /**
     * Required fields that must be present in the booking data
     */
    protected array $nullableFields = [
        // No nullable fields defined yet, can be added as needed
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'service_id' => ProductiveService::class,
        'event_id' => ProductiveEvent::class,
        'person_id' => ProductivePeople::class,
        'creator_id' => ProductivePeople::class,
        'updater_id' => ProductivePeople::class,
        'approver_id' => ProductivePeople::class,
        'rejecter_id' => ProductivePeople::class,
        'canceler_id' => ProductivePeople::class,
        'origin_id' => ProductiveBooking::class,
        'approval_status_id' => ProductiveApprovalStatus::class,
        'attachment_id' => ProductiveAttachment::class,
    ];

    /**
     * Store a booking in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $bookingData = $parameters['bookingData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$bookingData) {
            throw new \Exception('Booking data is nullable');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing booking: {$bookingData['id']}");
            }

            // Validate basic data structure
            if (!isset($bookingData['id'])) {
                throw new \Exception("Missing nullable field 'id' in root data object");
            }

            $attributes = $bookingData['attributes'] ?? [];
            $relationships = $bookingData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($bookingData['type'])) {
                $attributes['type'] = $bookingData['type'];
            }

            // Validate nullable fields
            $this->validateRequiredFields($attributes, $bookingData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $bookingData['id'],
                'type' => $attributes['type'] ?? $bookingData['type'] ?? 'bookings',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['hours'] ?? 'Unknown Booking', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update booking
            ProductiveBooking::updateOrCreate(
                ['id' => $bookingData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored booking: {$attributes['hours']} (ID: {$bookingData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store booking {$bookingData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store booking {$bookingData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all nullable fields are present
     *
     * @param array $attributes
     * @param string $bookingId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $bookingId, ?Command $command): void
    {
        // Skip validation if no nullable fields are defined
        if (empty($this->nullableFields)) {
            return;
        }
        // Check for missing nullable fields
        $missingFields = [];
        foreach ($this->nullableFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for booking {$bookingId}: " . implode(', ', $missingFields);
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
     * @param string $bookingName
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $bookingName, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'service' => 'service_id',
            'event' => 'event_id',
            'person' => 'person_id',
            'creator' => 'creator_id',
            'updater' => 'updater_id',
            'approver' => 'approver_id',
            'rejecter' => 'rejecter_id',
            'canceler' => 'canceler_id',
            'origin' => 'origin_id',
            'approval_statuses' => 'approval_status_id',
            'attachments' => 'attachment_id'
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
                        $command->warn("Booking '{$bookingName}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
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
            'hours' => 'nullable|numeric',
            'time' => 'nullable|integer',
            'started_on' => 'nullable|date',
            'ended_on' => 'nullable|date',
            'note' => 'nullable|string',
            'total_time' => 'nullable|integer',
            'total_working_days' => 'nullable|integer',
            'percentage' => 'nullable|integer',
            'created_at_api' => 'nullable|date',
            'updated_at_api' => 'nullable|date',
            'people_custom_fields' => 'nullable|string',
            'approved' => 'boolean',
            'approved_at_api' => 'nullable|date',
            'rejected' => 'boolean',
            'rejected_reason' => 'nullable|string',
            'rejected_at_api' => 'nullable|date',
            'canceled' => 'boolean',
            'canceled_at_api' => 'nullable|date',
            'booking_method_id' => 'integer',
            'autotracking' => 'boolean',
            'draft' => 'boolean',
            'custom_fields' => 'nullable|string',
            'external_id' => 'nullable|string',
            'last_activity_at_api' => 'nullable|date',
            'stage_type' => 'nullable|integer',
            // Foreign keys
            'service_id' => 'nullable|string',
            'event_id' => 'nullable|string',
            'person_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'updater_id' => 'nullable|string',
            'approver_id' => 'nullable|string',
            'rejecter_id' => 'nullable|string',
            'canceler_id' => 'nullable|string',
            'origin_id' => 'nullable|string',
            'approval_status_id' => 'nullable|string',
            'attachment_id' => 'nullable|string',
            // Custom fields can be arrays or JSON objects
            'custom_field_people' => 'nullable|array',
            'custom_field_attachments' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
