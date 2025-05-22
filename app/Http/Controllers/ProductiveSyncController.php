<?php

namespace App\Http\Controllers;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductiveTimeEntries;
use App\Models\ProductiveTimeEntryVersions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductiveSyncController extends Controller
{
    /**
     * Display sync status and last sync time
     */
    public function status()
    {
        try {
            $lastSync = Cache::get('productive_last_sync');
            $isCurrentlySyncing = Cache::get('productive_is_syncing', false);

            $stats = [
                'companies_count' => ProductiveCompany::count(),
                'projects_count' => ProductiveProject::count(),
                'deals_count' => ProductiveDeal::count(),
                'time_entries_count' => ProductiveTimeEntries::count(),
                'time_entry_versions_count' => ProductiveTimeEntryVersions::count(),
            ];

            // Get detailed relationship stats
            $relationshipStats = $this->getRelationshipStats();

            return response()->json([
                'last_sync' => $lastSync,
                'is_syncing' => $isCurrentlySyncing,
                'stats' => $stats,
                'relationships' => $relationshipStats
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sync status: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'Failed to get sync status',
                'status' => 'error'
            ], 500);
        }
    }
    /**
     * Trigger a manual sync
     */
    public function sync()
    {
        // Prevent multiple syncs running at once
        if (Cache::get('productive_is_syncing', false)) {
            return response()->json([
                'message' => 'Sync already in progress',
                'status' => 'error'
            ], 409);
        }

        try {
            Cache::put('productive_is_syncing', true, now()->addMinutes(30));

            // Store time before sync to measure duration
            $startTime = microtime(true);

            // Run the sync command
            $output = Artisan::call('sync:productive');

            if ($output !== 0) {
                throw new \RuntimeException('Sync command failed with exit code: ' . $output);
            }

            // Calculate execution time
            $executionTime = round(microtime(true) - $startTime, 2);

            // Get detailed relationship stats
            $relationshipStats = $this->getRelationshipStats();

            // Update last sync time
            Cache::put('productive_last_sync', now(), now()->addDays(30));
            Cache::forget('productive_is_syncing');

            return response()->json([
                'message' => 'Sync completed successfully',
                'status' => 'success',
                'last_sync' => now(),
                'execution_time' => $executionTime . ' seconds',
                'stats' => [
                    'companies_count' => ProductiveCompany::count(),
                    'projects_count' => ProductiveProject::count(),
                    'deals_count' => ProductiveDeal::count(),
                    'time_entries_count' => ProductiveTimeEntries::count(),
                    'time_entry_versions_count' => ProductiveTimeEntryVersions::count(),
                ],
                'relationships' => $relationshipStats
            ]);
        } catch (\Exception $e) {
            Log::error('Sync failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            Cache::forget('productive_is_syncing');

            return response()->json([
                'message' => 'Sync failed: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Get detailed stats about relationships between entities
     */
    private function getRelationshipStats(): array
    {
        try {
            $stats = [
                'companies' => [
                    'total' => ProductiveCompany::count(),
                    'with_projects' => ProductiveCompany::has('projects')->count(),
                    'with_deals' => ProductiveCompany::has('deals')->count(),
                ],
                'projects' => [
                    'total' => ProductiveProject::count(),
                    'with_company' => ProductiveProject::whereNotNull('company_id')->count(),
                    'with_deals' => ProductiveProject::has('deals')->count(),
                    'orphaned' => ProductiveProject::whereNull('company_id')->count(),
                ],
                'deals' => [
                    'total' => ProductiveDeal::count(),
                    'with_company' => ProductiveDeal::whereNotNull('company_id')->count(),
                    'with_project' => ProductiveDeal::whereNotNull('project_id')->count(),
                    'with_both' => ProductiveDeal::whereNotNull('company_id')
                        ->whereNotNull('project_id')
                        ->count(),
                    'orphaned' => ProductiveDeal::whereNull('company_id')
                        ->whereNull('project_id')
                        ->count(),
                ],
                'time_entries' => [
                    'total' => ProductiveTimeEntries::count(),
                    'with_task' => ProductiveTimeEntries::whereNotNull('task_id')->count(),
                    'with_service' => ProductiveTimeEntries::whereNotNull('service_id')->count(),
                    'with_person' => ProductiveTimeEntries::whereNotNull('person_id')->count(),
                    'billable' => ProductiveTimeEntries::where('billable_time', '>', 0)->count(),
                ],
                'time_entry_versions' => [
                    'total' => ProductiveTimeEntryVersions::count(),
                    'with_time_entry' => ProductiveTimeEntryVersions::whereNotNull('item_id')->count(),
                    'by_event' => ProductiveTimeEntryVersions::select('event', DB::raw('count(*) as count'))
                        ->groupBy('event')
                        ->pluck('count', 'event')
                        ->toArray()
                ]
            ];

            // Add percentage calculations for better readability
            // Skip arrays and nested objects when calculating percentages
            foreach ($stats as $entity => &$data) {
                $total = max(1, $data['total']); // Avoid division by zero

                foreach ($data as $key => $value) {
                    // Only calculate percentages for numeric values, skip arrays and 'total'
                    if ($key !== 'total' && is_numeric($value) && !is_array($value)) {
                        $data[$key . '_pct'] = round(($value / $total) * 100, 2);
                    }
                }
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting relationship stats: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            // Return basic stats if detailed stats fail
            return [
                'companies' => ['total' => ProductiveCompany::count()],
                'projects' => ['total' => ProductiveProject::count()],
                'deals' => ['total' => ProductiveDeal::count()],
                'time_entries' => ['total' => ProductiveTimeEntries::count()],
                'time_entry_versions' => ['total' => ProductiveTimeEntryVersions::count()],
            ];
        }
    }
}
