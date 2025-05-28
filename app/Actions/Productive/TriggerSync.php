<?php

namespace App\Actions\Productive;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TriggerSync extends AbstractAction
{    public function __construct(
        private readonly GetRelationshipStats $getRelationshipStats,
        private readonly GetSyncStatus $getSyncStatus
    ) {}

    /**
     * Execute the action to trigger a sync.
     *
     * @param array $parameters
     * @return array
     */
    public function handle(array $parameters = []): array
    {
        // Prevent multiple syncs running at once
        if (Cache::get('productive_is_syncing', false)) {
            return [
                'message' => 'Sync already in progress',
                'status' => 'error',
                'code' => 409
            ];
        }

        try {
            Cache::put('productive_is_syncing', true, now()->addMinutes(30));

            // Store time before sync to measure duration
            $startTime = microtime(true);            // Run the sync command - using refactored version
            $output = Artisan::call('sync:productive');

            if ($output !== 0) {
                throw new \RuntimeException('Sync command failed with exit code: ' . $output);
            }

            // Calculate execution time
            $executionTime = round(microtime(true) - $startTime, 2);            // Get detailed relationship stats using injected dependency
            $relationshipStats = $this->getRelationshipStats->handle();

            // Update last sync time
            Cache::put('productive_last_sync', now(), now()->addDays(30));
            Cache::forget('productive_is_syncing');

            // Get updated model counts using injected dependency
            $syncStatus = $this->getSyncStatus->handle();

            return [
                'message' => 'Sync completed successfully',
                'status' => 'success',
                'last_sync' => now(),
                'execution_time' => $executionTime . ' seconds',
                'stats' => $syncStatus['stats'],
                'relationships' => $relationshipStats,
                'code' => 200
            ];
        } catch (\Exception $e) {
            Log::error('Sync failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            Cache::forget('productive_is_syncing');

            return [
                'message' => 'Sync failed: ' . $e->getMessage(),
                'status' => 'error',
                'code' => 500
            ];
        }
    }
}
