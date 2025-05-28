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
                    'with_default_subsidiary' => ProductiveCompany::whereNotNull('default_subsidiary_id')->count(),
                    'with_default_tax_rate' => ProductiveCompany::whereNotNull('default_tax_rate_id')->count(),
                    'with_default_document_type' => ProductiveCompany::whereNotNull('default_document_type_id')->count(),
                    'archived' => ProductiveCompany::whereNotNull('archived_at')->count()
                ],
                'document_styles' => [
                    'total' => ProductiveDocumentStyle::count(),
                    'with_attachment' => ProductiveDocumentStyle::whereNotNull('attachment_id')->count(),
                    'used_by_document_types' => ProductiveDocumentStyle::has('documentTypes')->count(),
                    'orphaned' => ProductiveDocumentStyle::doesntHave('documentTypes')->count()
                ],
                'document_types' => [
                    'total' => ProductiveDocumentType::count(),
                    'with_subsidiary' => ProductiveDocumentType::whereNotNull('subsidiary_id')->count(),
                    'with_document_style' => ProductiveDocumentType::whereNotNull('document_style_id')->count(),
                    'with_attachment' => ProductiveDocumentType::whereNotNull('attachment_id')->count(),
                    'orphaned' => ProductiveDocumentType::whereNull('subsidiary_id')->count(),
                    'archived' => ProductiveDocumentType::whereNotNull('archived_at')->count()
                ],
                'projects' => [
                    'total' => ProductiveProject::count(),
                    'with_company' => ProductiveProject::whereNotNull('company_id')->count(),
                    'with_deals' => ProductiveProject::has('deals')->count(),
                    'with_time_entries' => ProductiveProject::whereHas('deals', function($q) {
                        $q->whereHas('timeEntries');
                    })->count(),
                    'archived' => ProductiveProject::whereNotNull('archived_at')->count(),
                    'orphaned' => ProductiveProject::whereNull('company_id')->count()
                ],
                'deals' => [
                    'total' => ProductiveDeal::count(),
                    'with_company' => ProductiveDeal::whereNotNull('company_id')->count(),
                    'with_project' => ProductiveDeal::whereNotNull('project_id')->count(),
                    'with_document_type' => ProductiveDeal::whereNotNull('document_type_id')->count(),
                    'with_pipeline' => ProductiveDeal::whereNotNull('pipeline_id')->count(),
                    'with_tax_rate' => ProductiveDeal::whereNotNull('tax_rate_id')->count(),
                    'with_subsidiary' => ProductiveDeal::whereNotNull('subsidiary_id')->count(),
                    'with_creator' => ProductiveDeal::whereNotNull('creator_id')->count(),
                    'with_responsible_person' => ProductiveDeal::whereNotNull('responsible_id')->count(),
                    'with_time_entries' => ProductiveDeal::has('timeEntries')->count(),
                    'orphaned' => ProductiveDeal::whereNull('company_id')->whereNull('project_id')->count()
                ],
                'time_entries' => [
                    'total' => ProductiveTimeEntry::count(),
                    'with_person' => ProductiveTimeEntry::whereNotNull('person_id')->count(),
                    'with_service' => ProductiveTimeEntry::whereNotNull('service_id')->count(),
                    'with_task' => ProductiveTimeEntry::whereNotNull('task_id')->count(),
                    'with_deal' => ProductiveTimeEntry::whereNotNull('deal_id')->count(),
                    'with_project' => ProductiveTimeEntry::whereNotNull('project_id')->count(),
                    'with_approver' => ProductiveTimeEntry::whereNotNull('approver_id')->count(),
                    'billable' => ProductiveTimeEntry::where('billable_time', '>', 0)->count(),
                    'approved' => ProductiveTimeEntry::whereNotNull('approved_at')->count(),
                    'rejected' => ProductiveTimeEntry::whereNotNull('rejected_at')->count(),
                    'submitted' => ProductiveTimeEntry::where('submitted', true)->count(),
                    'orphaned' => ProductiveTimeEntry::whereNull('person_id')->orWhereNull('deal_id')->count()
                ],
                'time_entry_versions' => [
                    'total' => ProductiveTimeEntryVersion::count(),
                    'with_time_entry' => ProductiveTimeEntryVersion::whereNotNull('item_id')->count(),
                    'by_event' => ProductiveTimeEntryVersion::select('event', DB::raw('count(*) as count'))
                        ->groupBy('event')
                        ->pluck('count', 'event')
                        ->toArray()
                ],
                'people' => [
                    'total' => ProductivePeople::count(),
                    'with_company' => ProductivePeople::whereNotNull('company_id')->count(),
                    'with_subsidiary' => ProductivePeople::whereNotNull('subsidiary_id')->count(),
                    'with_manager' => ProductivePeople::whereNotNull('manager_id')->count(),
                    'with_time_entries' => ProductivePeople::has('timeEntries')->count(),
                    'is_user' => ProductivePeople::where('is_user', true)->count(),
                    'is_virtual' => ProductivePeople::where('virtual', true)->count(),
                    'is_champion' => ProductivePeople::where('champion', true)->count(),
                    'archived' => ProductivePeople::whereNotNull('archived_at')->count()
                ]
            ];

            // Add percentage calculations for better readability
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
                'time_entries' => ['total' => ProductiveTimeEntry::count()],
                'time_entry_versions' => ['total' => ProductiveTimeEntryVersion::count()],
                'document_types' => ['total' => ProductiveDocumentType::count()],
                'document_styles' => ['total' => ProductiveDocumentStyle::count()],
                'people' => ['total' => ProductivePeople::count()]
            ];
        }
    }
}
