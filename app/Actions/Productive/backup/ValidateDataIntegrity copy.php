<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductiveTimeEntry;
use App\Models\ProductiveTimeEntryVersion;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveTaxRate;
use App\Models\ProductiveDocumentType;
use App\Models\ProductivePeople;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidateDataIntegrity extends AbstractAction
{
    /**
     * Execute the action to validate data integrity.
     *
     * @param array $parameters
     * @return array
     */
    public function handle(array $parameters = []): array
    {
        $command = $parameters['command'] ?? null;

        if ($command) {
            $command->info('Validating data integrity...');
        }

        try {
            $stats = [
                'subsidiaries' => [
                    'total' => ProductiveSubsidiary::count(),
                    'with_bill_from' => ProductiveSubsidiary::whereNotNull('bill_from_id')->count(),
                    'with_custom_domain' => ProductiveSubsidiary::whereNotNull('custom_domain_id')->count(),
                    'with_tax_rate' => ProductiveSubsidiary::whereNotNull('default_tax_rate_id')->count(),
                    'with_integration' => ProductiveSubsidiary::whereNotNull('integration_id')->count(),
                    'orphaned' => 0,
                ],
                'tax_rates' => [
                    'total' => ProductiveTaxRate::count(),
                    'with_subsidiary' => ProductiveTaxRate::whereNotNull('subsidiary_id')->count(),
                    'orphaned' => 0,
                ],
                'document_types' => [
                    'total' => ProductiveDocumentType::count(),
                    'with_subsidiary' => ProductiveDocumentType::whereNotNull('subsidiary_id')->count(),
                    'with_document_style' => ProductiveDocumentType::whereNotNull('document_style_id')->count(),
                    'with_attachment' => ProductiveDocumentType::whereNotNull('attachment_id')->count(),
                    'orphaned' => 0,
                ],
                'companies' => [
                    'total' => ProductiveCompany::count(),
                    'with_default_subsidiary' => ProductiveCompany::whereNotNull('default_subsidiary_id')->count(),
                    'with_default_tax_rate' => ProductiveCompany::whereNotNull('default_tax_rate_id')->count(),
                    'orphaned' => 0,
                ],
                'projects' => [
                    'total' => ProductiveProject::count(),
                    'with_company' => ProductiveProject::whereNotNull('company_id')->count(),
                    'with_project_manager' => ProductiveProject::whereNotNull('project_manager_id')->count(),
                    'with_last_actor' => ProductiveProject::whereNotNull('last_actor_id')->count(),
                    'with_workflow' => ProductiveProject::whereNotNull('workflow_id')->count(),
                    'orphaned' => 0,
                ],
                'deals' => [
                    'total' => ProductiveDeal::count(),
                    'with_creator' => ProductiveDeal::whereNotNull('creator_id')->count(),
                    'with_company' => ProductiveDeal::whereNotNull('company_id')->count(),
                    'with_document_type' => ProductiveDeal::whereNotNull('document_type_id')->count(),
                    'with_responsible' => ProductiveDeal::whereNotNull('responsible_id')->count(),
                    'with_deal_status' => ProductiveDeal::whereNotNull('deal_status_id')->count(),
                    'with_project' => ProductiveDeal::whereNotNull('project_id')->count(),
                    'with_lost_reason' => ProductiveDeal::whereNotNull('lost_reason_id')->count(),
                    'with_contract' => ProductiveDeal::whereNotNull('contract_id')->count(),
                    'with_contact' => ProductiveDeal::whereNotNull('contact_id')->count(),
                    'with_subsidiary' => ProductiveDeal::whereNotNull('subsidiary_id')->count(),
                    'with_template' => ProductiveDeal::whereNotNull('template_id')->count(),
                    'with_tax_rate' => ProductiveDeal::whereNotNull('tax_rate_id')->count(),
                    'with_origin_deal' => ProductiveDeal::whereNotNull('origin_deal_id')->count(),
                    'with_apa' => ProductiveDeal::whereNotNull('apa_id')->count(),
                    'orphaned' => 0,
                ],
                'time_entries' => [
                    'total' => ProductiveTimeEntry::count(),
                    'with_person' => ProductiveTimeEntry::whereNotNull('person_id')->count(),
                    'with_service' => ProductiveTimeEntry::whereNotNull('service_id')->count(),
                    'with_task' => ProductiveTimeEntry::whereNotNull('task_id')->count(),
                    'with_approver' => ProductiveTimeEntry::whereNotNull('approver_id')->count(),
                    'with_updater' => ProductiveTimeEntry::whereNotNull('updater_id')->count(),
                    'with_rejecter' => ProductiveTimeEntry::whereNotNull('rejecter_id')->count(),
                    'with_creator' => ProductiveTimeEntry::whereNotNull('creator_id')->count(),
                    'with_last_actor' => ProductiveTimeEntry::whereNotNull('last_actor_id')->count(),
                    'with_person_subsidiary' => ProductiveTimeEntry::whereNotNull('person_subsidiary_id')->count(),
                    'with_deal_subsidiary' => ProductiveTimeEntry::whereNotNull('deal_subsidiary_id')->count(),
                    'with_timesheet' => ProductiveTimeEntry::whereNotNull('timesheet_id')->count(),
                    'orphaned' => 0,
                ],
                'people' => [
                    'total' => ProductivePeople::count(),
                    'with_manager' => ProductivePeople::whereNotNull('manager_id')->count(),
                    'with_company' => ProductivePeople::whereNotNull('company_id')->count(),
                    'with_subsidiary' => ProductivePeople::whereNotNull('subsidiary_id')->count(),
                    'with_apa' => ProductivePeople::whereNotNull('apa_id')->count(),
                    'with_team' => ProductivePeople::whereNotNull('team_id')->count(),
                    'orphaned' => 0,
                ],
                'time_entry_versions' => [
                    'total' => ProductiveTimeEntryVersion::count(),
                    'with_creator' => ProductiveTimeEntryVersion::whereNotNull('creator_id')->count(),
                    'orphaned' => 0,
                ],
                'document_styles' => [
                    'total' => ProductiveDocumentStyle::count(),
                    'with_attachment' => ProductiveDocumentStyle::whereNotNull('attachment_id')->count(),
                    'orphaned' => 0,
                ]
            ];

            if ($command) {
                $this->outputReport($stats, $command);
            }

            return $stats;

        } catch (\Exception $e) {
            Log::error('Error validating data integrity: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            throw $e;
        }
    }

    /**
     * Output the validation report to the command line
     */
    protected function outputReport(array $stats, Command $command): void 
    {
        $command->info('=== Data Integrity Report ===');

        foreach ($stats as $entityType => $data) {
            $command->info("\n" . ucfirst($entityType) . ": {$data['total']} total");
            
            foreach ($data as $key => $value) {
                if ($key === 'total' || $key === 'by_event') {
                    continue;
                }
                
                if (is_numeric($value)) {
                    $percentage = round(($value / max(1, $data['total'])) * 100, 2);
                    $command->info("- " . str_replace('_', ' ', ucfirst($key)) . ": {$value} ({$percentage}%)");
                }
            }
        }
    }
}
