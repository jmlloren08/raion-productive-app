<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductiveTimeEntries;
use App\Models\ProductiveTimeEntryVersions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetRelationshipStats extends AbstractAction
{
    /**
     * Execute the action to get relationship statistics.
     *
     * @param array $parameters
     * @return array
     */
    public function handle(array $parameters = []): array
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
