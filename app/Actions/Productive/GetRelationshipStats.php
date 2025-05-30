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
use App\Models\ProductivePurchaseOrder;
use App\Models\ProductiveApa;
use App\Models\ProductiveApprovalPolicy;
use App\Models\ProductivePipeline;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveBill;
use App\Models\ProductiveTeam;
use App\Models\ProductiveEmail;
use App\Models\ProductiveInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;

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
        $command = $parameters['command'] ?? null;

        try {
            $stats = [
                'companies' => [
                    'total' => ProductiveCompany::count(),
                    'with_default_subsidiary' => ProductiveCompany::whereNotNull('default_subsidiary_id')->count(),
                    'with_default_tax_rate' => ProductiveCompany::whereNotNull('default_tax_rate_id')->count(),
                    'with_document_style' => ProductiveCompany::whereNotNull('document_style_id')->count()
                ],
                'document_styles' => [
                    'total' => ProductiveDocumentStyle::count(),
                    'with_attachment' => ProductiveDocumentStyle::whereNotNull('attachment_id')->count(),
                ],
                'document_types' => [
                    'total' => ProductiveDocumentType::count(),
                    'with_subsidiary' => ProductiveDocumentType::whereNotNull('subsidiary_id')->count(),
                    'with_document_style' => ProductiveDocumentType::whereNotNull('document_style_id')->count(),
                    'with_attachment' => ProductiveDocumentType::whereNotNull('attachment_id')->count(),
                ],
                'projects' => [
                    'total' => ProductiveProject::count(),
                    'with_company' => ProductiveProject::whereNotNull('company_id')->count(),
                    'with_project_manager' => ProductiveProject::whereNotNull('project_manager_id')->count(),
                    'with_last_actor' => ProductiveProject::whereNotNull('last_actor_id')->count(),
                    'with_workflow' => ProductiveProject::whereNotNull('workflow_id')->count(),
                ],
                'people' => [
                    'total' => ProductivePeople::count(),
                    'with_manager' => ProductivePeople::whereNotNull('manager_id')->count(),
                    'with_company' => ProductivePeople::whereNotNull('company_id')->count(),
                    'with_subsidiary' => ProductivePeople::whereNotNull('subsidiary_id')->count(),
                    'with_tax_rate' => ProductivePeople::whereNotNull('tax_rate_id')->count(),
                    'with_apa' => ProductivePeople::whereNotNull('apa_id')->count(),
                ],
                'tax_rates' => [
                    'total' => ProductiveTaxRate::count(),
                    'with_subsidiary' => ProductiveTaxRate::whereNotNull('subsidiary_id')->count(),
                ],
                'subsidiaries' => [
                    'total' => ProductiveSubsidiary::count(),
                    'with_contact_entry' => ProductiveSubsidiary::whereNotNull('contact_entry_id')->count(),
                    'with_custom_domain' => ProductiveSubsidiary::whereNotNull('custom_domain_id')->count(),
                    'with_tax_rate' => ProductiveSubsidiary::whereNotNull('default_tax_rate_id')->count(),
                    'with_integration' => ProductiveSubsidiary::whereNotNull('integration_id')->count(),
                ],
                'workflows' => [
                    'total' => ProductiveWorkflow::count(),
                    'with_workflow_status' => ProductiveWorkflow::whereNotNull('workflow_status_id')->count(),
                ],
                'contact_entries' => [
                    'total' => ProductiveContactEntry::count(),
                    'with_company' => ProductiveContactEntry::whereNotNull('company_id')->count(),
                    'with_person' => ProductiveContactEntry::whereNotNull('person_id')->count(),
                    'with_invoice' => ProductiveContactEntry::whereNotNull('invoice_id')->count(),
                    'with_subsidiary' => ProductiveContactEntry::whereNotNull('subsidiary_id')->count(),
                    'with_purchase_order' => ProductiveContactEntry::whereNotNull('purchase_order_id')->count(),
                ],
                'lost_reasons' => [
                    'total' => ProductiveLostReason::count(),
                ],
                'pipelines' => [
                    'total' => ProductivePipeline::count(),
                    'with_creator' => ProductivePipeline::whereNotNull('creator_id')->count(),
                    'with_updater' => ProductivePipeline::whereNotNull('updater_id')->count(),
                ],
                'deal_statuses' => [
                    'total' => ProductiveDealStatus::count(),
                    'with_pipeline' => ProductiveDealStatus::whereNotNull('pipeline_id')->count(),
                ],
                'approval_policies' => [
                    'total' => ProductiveApprovalPolicy::count(),
                ],
                'approval_policy_assignments' => [
                    'total' => ProductiveApa::count(),
                    'with_person' => ProductiveApa::whereNotNull('person_id')->count(),
                    'with_deal' => ProductiveApa::whereNotNull('deal_id')->count(),
                    'with_approval_policy' => ProductiveApa::whereNotNull('approval_policy_id')->count(),
                ],
                'contracts' => [
                    'total' => ProductiveContract::count(),
                    'with_deal' => ProductiveContract::whereNotNull('deal_id')->count(),
                ],
                'deals' => [
                    'total' => ProductiveDeal::count(),
                    'with_creator' => ProductiveDeal::whereNotNull('creator_id')->count(),
                    'with_company' => ProductiveDeal::whereNotNull('company_id')->count(),
                    'with_document_type' => ProductiveDeal::whereNotNull('document_type_id')->count(),
                    'with_responsible_person' => ProductiveDeal::whereNotNull('responsible_id')->count(),
                    'with_deal_status' => ProductiveDeal::whereNotNull('deal_status_id')->count(),
                    'with_project' => ProductiveDeal::whereNotNull('project_id')->count(),
                    'with_lost_reason' => ProductiveDeal::whereNotNull('lost_reason_id')->count(),
                    'with_contract' => ProductiveDeal::whereNotNull('contract_id')->count(),
                    'with_contact' => ProductiveDeal::whereNotNull('contact_id')->count(),
                    'with_subsidiary' => ProductiveDeal::whereNotNull('subsidiary_id')->count(),
                    'with_tax_rate' => ProductiveDeal::whereNotNull('tax_rate_id')->count(),
                    'with_apa' => ProductiveDeal::whereNotNull('apa_id')->count(),
                ],
                'purchase_orders' => [
                    'total' => ProductivePurchaseOrder::count(),
                    'with_company' => ProductivePurchaseOrder::whereNotNull('company_id')->count(),
                    'with_contact_entry' => ProductivePurchaseOrder::whereNotNull('contact_entry_id')->count(),
                    'with_subsidiary' => ProductivePurchaseOrder::whereNotNull('subsidiary_id')->count(),
                    'with_tax_rate' => ProductivePurchaseOrder::whereNotNull('tax_rate_id')->count(),
                    'with_document_type' => ProductivePurchaseOrder::whereNotNull('document_type_id')->count(),
                    'with_document_style' => ProductivePurchaseOrder::whereNotNull('document_style_id')->count()
                ],
                'emails' => [
                    'total' => ProductiveEmail::count(),
                    'with_creator' => ProductiveEmail::whereNotNull('creator_id')->count(),
                    'with_deal' => ProductiveEmail::whereNotNull('deal_id')->count(),
                    'with_invoice' => ProductiveEmail::whereNotNull('invoice_id')->count(),
                    'with_payment_reminder_sequence' => ProductiveEmail::whereNotNull('payment_reminder_sequence_id')->count(),
                    'with_attachment' => ProductiveEmail::whereNotNull('attachment_id')->count(),
                ],
                'bills' => [
                    'total' => ProductiveBill::count(),
                    'with_purchase_order' => ProductiveBill::whereNotNull('purchase_order_id')->count(),
                    'with_creator' => ProductiveBill::whereNotNull('creator_id')->count(),
                    'with_deal' => ProductiveBill::whereNotNull('deal_id')->count(),
                    'with_attachment' => ProductiveBill::whereNotNull('attachment_id')->count(),
                ],
                'attachments' => [
                    'total' => ProductiveAttachment::count(),
                    'with_creator' => ProductiveAttachment::whereNotNull('creator_id')->count(),
                    'with_invoice' => ProductiveAttachment::whereNotNull('invoice_id')->count(),
                    'with_purchase_order' => ProductiveAttachment::whereNotNull('purchase_order_id')->count(),
                    'with_bill' => ProductiveAttachment::whereNotNull('bill_id')->count(),
                    'with_email' => ProductiveAttachment::whereNotNull('email_id')->count(),
                    'with_page' => ProductiveAttachment::whereNotNull('page_id')->count(),
                    'with_expense' => ProductiveAttachment::whereNotNull('expense_id')->count(),
                    'with_comment' => ProductiveAttachment::whereNotNull('comment_id')->count(),
                    'with_task' => ProductiveAttachment::whereNotNull('task_id')->count(),
                    'with_document_style' => ProductiveAttachment::whereNotNull('document_style_id')->count(),
                    'with_document_type' => ProductiveAttachment::whereNotNull('document_type_id')->count(),
                    'with_deal' => ProductiveAttachment::whereNotNull('deal_id')->count(),
                ],
                'teams' => [
                    'total' => ProductiveTeam::count(),
                ],
                'invoices' => [
                    'total' => ProductiveInvoice::count(),
                    'with_company' => ProductiveInvoice::whereNotNull('company_id')->count(),
                    'with_creator' => ProductiveInvoice::whereNotNull('creator_id')->count(),
                    'with_deal' => ProductiveInvoice::whereNotNull('deal_id')->count(),
                    'with_contact_entry' => ProductiveInvoice::whereNotNull('contact_entry_id')->count(),
                    'with_subsidiary' => ProductiveInvoice::whereNotNull('subsidiary_id')->count(),
                    'with_tax_rate' => ProductiveInvoice::whereNotNull('tax_rate_id')->count(),
                    'with_document_type' => ProductiveInvoice::whereNotNull('document_type_id')->count(),
                    'with_document_style' => ProductiveInvoice::whereNotNull('document_style_id')->count(),
                    'with_attachment' => ProductiveInvoice::whereNotNull('attachment_id')->count()
                ],
            ];

            // Output report if command is provided
            if ($command instanceof Command) {
                $command->info('Relationship Statistics Report:');
                $command->info('===========================');

                foreach ($stats as $entity => $entityStats) {
                    $command->info("\n{$entity}:");
                    $command->info("- Total: {$entityStats['total']}");
                    foreach ($entityStats as $key => $value) {
                        if ($key !== 'total' && !str_ends_with($key, '_pct')) {
                            $percentage = $entityStats[$key . '_pct'] ?? 0;
                            $command->info("- {$key}: {$value} ({$percentage}%)");
                        }
                    }
                }
            }

            return [
                'projects' => $stats['projects'],
                'companies' => $stats['companies'],
                'subsidiaries' => $stats['subsidiaries'],
                'deals' => $stats['deals'],
                'people' => $stats['people'],
                'purchase_orders' => $stats['purchase_orders'],
                'approval_policy_assignments' => $stats['approval_policy_assignments'],
                'approval_policies' => $stats['approval_policies'],
                'pipelines' => $stats['pipelines'],
                'emails' => $stats['emails'],
                'bills' => $stats['bills'],
                'attachments' => $stats['attachments'],
                'teams' => $stats['teams'],
                'invoices' => $stats['invoices'],
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Error getting relationship statistics: ' . $e->getMessage());
            }
            // Return basic stats if detailed stats fail
            return [
                'companies' => ['total' => ProductiveCompany::count()],
                'projects' => ['total' => ProductiveProject::count()],
                // 'time_entries' => ['total' => ProductiveTimeEntry::count()],
                // 'time_entry_versions' => ['total' => ProductiveTimeEntryVersion::count()],
                'document_types' => ['total' => ProductiveDocumentType::count()],
                'document_styles' => ['total' => ProductiveDocumentStyle::count()],
                'people' => ['total' => ProductivePeople::count()],
                'tax_rates' => ['total' => ProductiveTaxRate::count()],
                'subsidiaries' => ['total' => ProductiveSubsidiary::count()],
                'workflows' => ['total' => ProductiveWorkflow::count()],
                'contact_entries' => ['total' => ProductiveContactEntry::count()],
                'lost_reasons' => ['total' => ProductiveLostReason::count()],
                'deal_statuses' => ['total' => ProductiveDealStatus::count()],
                'contracts' => ['total' => ProductiveContract::count()],
                'purchase_orders' => ['total' => ProductivePurchaseOrder::count()],
                'deals' => ['total' => ProductiveDeal::count()],
                'approval_policies' => ['total' => ProductiveApprovalPolicy::count()],
                'approval_policy_assignments' => ['total' => ProductiveApa::count()],
                'pipelines' => ['total' => ProductivePipeline::count()],
                'emails' => ['total' => ProductiveEmail::count()],
                'bills' => ['total' => ProductiveBill::count()],
                'attachments' => ['total' => ProductiveAttachment::count()],
                'teams' => ['total' => ProductiveTeam::count()],
                'invoices' => ['total' => ProductiveInvoice::count()],
            ];
        }
    }
}
