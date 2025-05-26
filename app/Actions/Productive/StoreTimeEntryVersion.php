<?php

namespace App\Actions\Productive;

use App\Models\ProductiveTimeEntries;
use App\Models\ProductiveTimeEntryVersions;
use Illuminate\Console\Command;

class StoreTimeEntryVersion extends AbstractAction
{
    /**
     * Execute the action to store a time entry version.
     *
     * @param array $parameters
     * @return void
     */
    public function handle(array $parameters = []): void
    {
        $timeEntryVersionData = $parameters['timeEntryVersionData'];
        $command = $parameters['command'] ?? null;
        
        $creatorId = null;
        if (isset($timeEntryVersionData['relationships']['creator']['data']['id'])) {
            $creatorId = $timeEntryVersionData['relationships']['creator']['data']['id'];
        }

        $organizationId = null;
        if (isset($timeEntryVersionData['relationships']['organization']['data']['id'])) {
            $organizationId = $timeEntryVersionData['relationships']['organization']['data']['id'];
        }

        $attributes = $timeEntryVersionData['attributes'] ?? [];
        
        // Check if the time entry exists in our database before trying to create a version
        $itemId = $attributes['item_id'] ?? null;
        $timeEntryExists = false;
        
        if ($itemId) {
            $timeEntryExists = ProductiveTimeEntries::where('id', $itemId)->exists();
            if (!$timeEntryExists) {
                if ($command) {
                    $command->warn("Time entry version (ID: {$timeEntryVersionData['id']}) references time entry ID: {$itemId}, but this time entry doesn't exist in our database. Setting item_id to null.");
                }
                $itemId = null; // Set to null to avoid foreign key constraint violation
            }
        }

        // Prepare data with safe fallbacks for all fields
        $data = [
            'id' => $timeEntryVersionData['id'],
            'type' => $timeEntryVersionData['type'] ?? 'time_entry_versions',
            
            // Foreign keys
            'item_id' => $itemId, // Use the verified item_id
            'creator_id' => $creatorId,
            'organization_id' => $organizationId,
            
            // Event details
            'event' => $attributes['event'] ?? null,
            'item_type' => $attributes['item_type'] ?? null,
            
            // JSON field for changes
            'object_changes' => is_array($attributes['object_changes']) 
                ? json_encode($attributes['object_changes']) 
                : $attributes['object_changes'] ?? null,
            
            // Timestamps
            'created_at_api' => $attributes['created_at'] ?? null,
        ];

        try {
            ProductiveTimeEntryVersions::updateOrCreate(
                ['id' => $timeEntryVersionData['id']],
                $data
            );
            
            if ($command) {
                if ($itemId) {
                    $command->info("Stored time entry version (ID: {$timeEntryVersionData['id']}) for time entry ID: {$itemId}");
                } else {
                    $command->info("Stored time entry version (ID: {$timeEntryVersionData['id']}) without time entry reference");
                }
            }
        } catch (\Exception $e) {
            if ($command) {
                $command->error("Failed to store time entry version (ID: {$timeEntryVersionData['id']}): " . $e->getMessage());
                // Log additional details for troubleshooting
                $command->warn("Time entry version data: " . json_encode([
                    'id' => $timeEntryVersionData['id'],
                    'event' => $attributes['event'] ?? 'Unknown Event',
                    'item_id' => $attributes['item_id'] ?? null,
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
