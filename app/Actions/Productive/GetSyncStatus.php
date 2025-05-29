<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductiveTimeEntry;
use App\Models\ProductiveTimeEntryVersion;
use App\Models\ProductiveDocumentType;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductivePeople;
use App\Models\ProductiveTaxRate;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveWorkflow;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveLostReason;
use App\Models\ProductiveDealStatus;
use App\Models\ProductiveContract;
use Illuminate\Console\Command;
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
        $command = $parameters['command'] ?? null;

        try {
            $lastSync = Cache::get('productive_last_sync');
            $isCurrentlySyncing = Cache::get('productive_is_syncing', false);

            $stats = [
                'companies_count' => ProductiveCompany::count(),
                'projects_count' => ProductiveProject::count(),
                'time_entries_count' => ProductiveTimeEntry::count(),
                'time_entry_versions_count' => ProductiveTimeEntryVersion::count(),
                'document_types_count' => ProductiveDocumentType::count(),
                'document_styles_count' => ProductiveDocumentStyle::count(),
                'people_count' => ProductivePeople::count(),
                'tax_rates_count' => ProductiveTaxRate::count(),
                'subsidiaries_count' => ProductiveSubsidiary::count(),
                'workflows_count' => ProductiveWorkflow::count(),
                'contact_entries_count' => ProductiveContactEntry::count(),
                'lost_reasons_count' => ProductiveLostReason::count(),
                'deal_statuses_count' => ProductiveDealStatus::count(),
                'contracts_count' => ProductiveContract::count(),
                'deals_count' => ProductiveDeal::count(),
            ];
            // Get detailed relationship stats
            $relationshipStats = $this->getRelationshipStatsAction->handle();

            if ($command instanceof Command) {
                $command->info('Sync Status Report:');
                $command->info('=================');
                foreach ($stats as $key => $value) {
                    $command->info("- {$key}: {$value}");
                }
            }

            return [
                'last_sync' => $lastSync,
                'is_syncing' => $isCurrentlySyncing,
                'stats' => $stats,
                'relationships' => $relationshipStats
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Error getting sync status: ' . $e->getMessage());
            }
            Log::error('Error getting sync status: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return [];
        }
    }
}
