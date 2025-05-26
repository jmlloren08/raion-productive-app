<?php

namespace App\Actions\Productive;

use App\Models\ProductiveTimeEntries;
use Illuminate\Console\Command;

class StoreTimeEntry extends AbstractAction
{
    /**
     * Execute the action to store a time entry.
     *
     * @param array $parameters
     * @return void
     */
    public function handle(array $parameters = []): void
    {
        $timeEntryData = $parameters['timeEntryData'];
        $command = $parameters['command'] ?? null;
        
        $attributes = $timeEntryData['attributes'] ?? [];
        $relationships = $timeEntryData['relationships'] ?? [];

        // Extract foreign keys from relationships
        $taskId = isset($relationships['task']['data']['id']) ? $relationships['task']['data']['id'] : null;
        $serviceId = isset($relationships['service']['data']['id']) ? $relationships['service']['data']['id'] : null;
        $personId = isset($relationships['person']['data']['id']) ? $relationships['person']['data']['id'] : null;
        $dealId = isset($relationships['deal']['data']['id']) ? $relationships['deal']['data']['id'] : null;
        $organizationId = isset($relationships['organization']['data']['id']) ? $relationships['organization']['data']['id'] : null;

        // Prepare data with safe fallbacks for all fields
        $data = [
            'id' => $timeEntryData['id'],
            'type' => $timeEntryData['type'] ?? 'time_entries',
            'date' => $attributes['date'] ?? null,
            
            // Foreign keys
            'task_id' => $taskId,
            'service_id' => $serviceId,
            'person_id' => $personId,
            'deal_id' => $dealId,
            'organization_id' => $organizationId,
            
            // Time values
            'time' => $attributes['time'] ?? 0,
            'billable_time' => $attributes['billable_time'] ?? 0,
            'after_hours_time' => $attributes['after_hours_time'] ?? 0,
            'length' => $attributes['length'] ?? 0,
            
            // Notes and metadata
            'note' => $attributes['note'] ?? null,
            'created_at_api' => $attributes['created_at'] ?? null,
            'updated_at_api' => $attributes['updated_at'] ?? null,
            'locked_at' => $attributes['locked_at'] ?? null,
            
            // Internal fields
            'billable' => $attributes['billable'] ?? false,
            'after_hours' => $attributes['after_hours'] ?? false,
            'month_year' => $attributes['month_year'] ?? null,
            'origin_id' => $attributes['origin_id'] ?? null,
            'external_referrer_id' => $attributes['external_referrer_id'] ?? null,
            'document_type_id' => $attributes['document_type_id'] ?? null,
            'person_subsidiary_id' => $attributes['person_subsidiary_id'] ?? null,
            'deal_subsidiary_id' => $attributes['deal_subsidiary_id'] ?? null,
            'timesheet_id' => $attributes['timesheet_id'] ?? null,
            
            // Currency
            'currency' => $attributes['currency'] ?? null,
            'currency_default' => $attributes['currency_default'] ?? null,
            'currency_normalized' => $attributes['currency_normalized'] ?? null,
            
            // Store the productive ID as a reference
            'productive_id' => $timeEntryData['id']
        ];

        try {
            ProductiveTimeEntries::updateOrCreate(
                ['id' => $timeEntryData['id']],
                $data
            );
            
            if ($command) {
                $command->info("Stored time entry (ID: {$timeEntryData['id']})");
            }
        } catch (\Exception $e) {
            if ($command) {
                $command->error("Failed to store time entry (ID: {$timeEntryData['id']}): " . $e->getMessage());
                // Log additional details for troubleshooting
                $command->warn("Time entry data: " . json_encode([
                    'id' => $timeEntryData['id'],
                    'date' => $attributes['date'] ?? 'Unknown Date',
                    'time' => $attributes['time'] ?? 0
                ]));
                
                // If it's an SQL error with column info, log it for debugging
                if (strpos($e->getMessage(), 'SQL') !== false) {
                    $command->warn("SQL Error: " . $e->getMessage());
                }
            } else {
                throw $e;
            }
        }
    }
}
