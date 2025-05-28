<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductiveTimeEntry;
use App\Models\ProductiveTimeEntryVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GetSyncStatus extends AbstractAction
{
    public function __construct(
        private GetRelationshipStats $getRelationshipStatsAction
    ) {}

    /**
     * Execute the action to get sync status.
     *
     * @param array $parameters
     * @return array
     */
    public function handle(array $parameters = []): array
    {
        try {
            $lastSync = Cache::get('productive_last_sync');
            $isCurrentlySyncing = Cache::get('productive_is_syncing', false);

            $stats = [
                'companies_count' => ProductiveCompany::count(),
                'projects_count' => ProductiveProject::count(),
                'deals_count' => ProductiveDeal::count(),
                'time_entries_count' => ProductiveTimeEntry::count(),
                'time_entry_versions_count' => ProductiveTimeEntryVersion::count(),
            ];
            // Get detailed relationship stats
            $relationshipStats = $this->getRelationshipStatsAction->handle();

            return [
                'last_sync' => $lastSync,
                'is_syncing' => $isCurrentlySyncing,
                'stats' => $stats,
                'relationships' => $relationshipStats
            ];
        } catch (\Exception $e) {
            Log::error('Error getting sync status: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            throw $e;
        }
    }
}
