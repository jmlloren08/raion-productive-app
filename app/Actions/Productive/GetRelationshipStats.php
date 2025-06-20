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
use App\Models\ProductiveInvoiceAttribution;
use App\Models\ProductiveBoard;
use App\Models\ProductiveBooking;
use App\Models\ProductiveCfo;
use App\Models\ProductiveComment;
use App\Models\ProductiveCustomDomain;
use App\Models\ProductiveCustomField;
use App\Models\ProductiveDiscussion;
use App\Models\ProductiveEvent;
use App\Models\ProductiveExpense;
use App\Models\ProductiveIntegration;
use App\Models\ProductivePage;
use App\Models\ProductivePaymentReminder;
use App\Models\ProductivePrs;
use App\Models\ProductiveSection;
use App\Models\ProductiveService;
use App\Models\ProductiveServiceType;
use App\Models\ProductiveTag;
use App\Models\ProductiveTask;
use App\Models\ProductiveTaskList;
use App\Models\ProductiveTimesheet;
use App\Models\ProductiveTodo;
use App\Models\ProductiveWorkflowStatus;
use Illuminate\Console\Command;
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
        $command = $parameters['command'] ?? null;

        try {
            $stats = [
                'companies' => [
                    'total' => ProductiveCompany::count(),
                    'with_default_subsidiary' => ProductiveCompany::whereNotNull('default_subsidiary_id')->count(),
                    'with_default_tax_rate' => ProductiveCompany::whereNotNull('default_tax_rate_id')->count(),
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
                    'with_apa_id' => ProductivePeople::whereNotNull('apa_id')->count(),
                    'with_team_id' => ProductivePeople::whereNotNull('team_id')->count(),
                ],
                'workflows' => [
                    'total' => ProductiveWorkflow::count(),
                    'with_workflow_status' => ProductiveWorkflow::whereNotNull('workflow_status_id')->count()
                ],
                'document_types' => [
                    'total' => ProductiveDocumentType::count(),
                    'with_subsidiary' => ProductiveDocumentType::whereNotNull('subsidiary_id')->count(),
                    'with_document_style' => ProductiveDocumentType::whereNotNull('document_style_id')->count(),
                    'with_attachment' => ProductiveDocumentType::whereNotNull('attachment_id')->count(),
                ],
                'subsidiaries' => [
                    'total' => ProductiveSubsidiary::count(),
                    'with_contact_entry' => ProductiveSubsidiary::whereNotNull('contact_entry_id')->count(),
                    'with_custom_domain' => ProductiveSubsidiary::whereNotNull('custom_domain_id')->count(),
                    'with_tax_rate' => ProductiveSubsidiary::whereNotNull('default_tax_rate_id')->count(),
                    'with_integration' => ProductiveSubsidiary::whereNotNull('integration_id')->count(),
                ],
                'tax_rates' => [
                    'total' => ProductiveTaxRate::count(),
                    'with_subsidiary' => ProductiveTaxRate::whereNotNull('subsidiary_id')->count(),
                ],
                'document_styles' => [
                    'total' => ProductiveDocumentStyle::count(),
                    'with_attachment' => ProductiveDocumentStyle::whereNotNull('attachment_id')->count(),
                ],
                'pipelines' => [
                    'total' => ProductivePipeline::count(),
                    'with_creator' => ProductivePipeline::whereNotNull('creator_id')->count(),
                    'with_updater' => ProductivePipeline::whereNotNull('updater_id')->count()
                ],
                'deal_statuses' => [
                    'total' => ProductiveDealStatus::count(),
                    'with_pipeline' => ProductiveDealStatus::whereNotNull('pipeline_id')->count()
                ],
                'lost_reasons' => [
                    'total' => ProductiveLostReason::count(),
                ],
                'contracts' => [
                    'total' => ProductiveContract::count(),
                    'with_deal' => ProductiveContract::whereNotNull('deal_id')->count(),
                ],
                'purchase_orders' => [
                    'total' => ProductivePurchaseOrder::count(),
                    'with_deal' => ProductivePurchaseOrder::whereNotNull('deal_id')->count(),
                    'with_creator' => ProductivePurchaseOrder::whereNotNull('creator_id')->count(),
                    'with_document_type' => ProductivePurchaseOrder::whereNotNull('document_type_id')->count(),
                    'with_attachment' => ProductivePurchaseOrder::whereNotNull('attachment_id')->count(),
                    'with_bill_to' => ProductivePurchaseOrder::whereNotNull('bill_to_id')->count(),
                    'with_bill_from' => ProductivePurchaseOrder::whereNotNull('bill_from_id')->count(),
                ],
                'contact_entries' => [
                    'total' => ProductiveContactEntry::count(),
                    'with_company' => ProductiveContactEntry::whereNotNull('company_id')->count(),
                    'with_person' => ProductiveContactEntry::whereNotNull('person_id')->count(),
                    'with_invoice' => ProductiveContactEntry::whereNotNull('invoice_id')->count(),
                    'with_subsidiary' => ProductiveContactEntry::whereNotNull('subsidiary_id')->count(),
                    'with_purchase_order' => ProductiveContactEntry::whereNotNull('purchase_order_id')->count()
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
                'emails' => [
                    'total' => ProductiveEmail::count(),
                    'with_creator' => ProductiveEmail::whereNotNull('creator_id')->count(),
                    'with_deal' => ProductiveEmail::whereNotNull('deal_id')->count(),
                    'with_invoice' => ProductiveEmail::whereNotNull('invoice_id')->count(),
                    'with_payment_reminder_sequence' => ProductiveEmail::whereNotNull('prs_id')->count(),
                    'with_attachment' => ProductiveEmail::whereNotNull('attachment_id')->count()
                ],
                'bills' => [
                    'total' => ProductiveBill::count(),
                    'with_purchase_order' => ProductiveBill::whereNotNull('purchase_order_id')->count(),
                    'with_creator' => ProductiveBill::whereNotNull('creator_id')->count(),
                    'with_deal' => ProductiveBill::whereNotNull('deal_id')->count(),
                    'with_attachment' => ProductiveBill::whereNotNull('attachment_id')->count()
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
                    'with_bill_to' => ProductiveInvoice::whereNotNull('bill_to_id')->count(),
                    'with_bill_from' => ProductiveInvoice::whereNotNull('bill_from_id')->count(),
                    'with_company' => ProductiveInvoice::whereNotNull('company_id')->count(),
                    'with_document_type' => ProductiveInvoice::whereNotNull('document_type_id')->count(),
                    'with_subsidiary' => ProductiveInvoice::whereNotNull('subsidiary_id')->count(),
                    'with_parent_invoice' => ProductiveInvoice::whereNotNull('parent_invoice_id')->count(),
                    'with_issuer' => ProductiveInvoice::whereNotNull('issuer_id')->count(),
                    'with_invoice_attribution' => ProductiveInvoice::whereNotNull('invoice_attribution_id')->count(),
                    'with_attachment' => ProductiveInvoice::whereNotNull('attachment_id')->count(),
                ],
                'invoice_attributions' => [
                    'total' => ProductiveInvoiceAttribution::count(),
                    'with_invoice' => ProductiveInvoiceAttribution::whereNotNull('invoice_id')->count(),
                    'with_budget' => ProductiveInvoiceAttribution::whereNotNull('budget_id')->count(),
                ],
                'boards' => [
                    'total' => ProductiveBoard::count(),
                    'with_project' => ProductiveBoard::whereNotNull('project_id')->count(),
                ],
                'bookings' => [
                    'total' => ProductiveBooking::count(),
                    'with_service' => ProductiveBooking::whereNotNull('service_id')->count(),
                    'with_event' => ProductiveBooking::whereNotNull('event_id')->count(),
                    'with_person' => ProductiveBooking::whereNotNull('person_id')->count(),
                    'with_creator' => ProductiveBooking::whereNotNull('creator_id')->count(),
                    'with_updater' => ProductiveBooking::whereNotNull('updater_id')->count(),
                    'with_approver' => ProductiveBooking::whereNotNull('approver_id')->count(),
                    'with_rejecter' => ProductiveBooking::whereNotNull('rejecter_id')->count(),
                    'with_canceler' => ProductiveBooking::whereNotNull('canceler_id')->count(),
                    'with_origin' => ProductiveBooking::whereNotNull('origin_id')->count(),
                    'with_approval_status' => ProductiveBooking::whereNotNull('approval_status_id')->count(),
                    'with_attachment' => ProductiveBooking::whereNotNull('attachment_id')->count(),
                ],
                'comments' => [
                    'total' => ProductiveComment::count(),
                    'with_company' => ProductiveComment::whereNotNull('company_id')->count(),
                    'with_creator' => ProductiveComment::whereNotNull('creator_id')->count(),
                    'with_deal' => ProductiveComment::whereNotNull('deal_id')->count(),
                    'with_discussion' => ProductiveComment::whereNotNull('discussion_id')->count(),
                    'with_invoice' => ProductiveComment::whereNotNull('invoice_id')->count(),
                    'with_person' => ProductiveComment::whereNotNull('person_id')->count(),
                    'with_pinned_by' => ProductiveComment::whereNotNull('pinned_by_id')->count(),
                    'with_task' => ProductiveComment::whereNotNull('task_id')->count(),
                    'with_purchase_order' => ProductiveComment::whereNotNull('purchase_order_id')->count(),
                    'with_attachment' => ProductiveComment::whereNotNull('attachment_id')->count(),
                ],
                'discussions' => [
                    'total' => ProductiveDiscussion::count(),
                    'with_page' => ProductiveDiscussion::whereNotNull('page_id')->count(),
                ],
                'events' => [
                    'total' => ProductiveEvent::count(),
                ],
                'expenses' => [
                    'total' => ProductiveExpense::count(),
                    'with_deal' => ProductiveExpense::whereNotNull('deal_id')->count(),
                    'with_service_type' => ProductiveExpense::whereNotNull('service_type_id')->count(),
                    'with_person' => ProductiveExpense::whereNotNull('person_id')->count(),
                    'with_creator' => ProductiveExpense::whereNotNull('creator_id')->count(),
                    'with_approver' => ProductiveExpense::whereNotNull('approver_id')->count(),
                    'with_rejecter' => ProductiveExpense::whereNotNull('rejecter_id')->count(),
                    'with_service' => ProductiveExpense::whereNotNull('service_id')->count(),
                    'with_purchase_order' => ProductiveExpense::whereNotNull('purchase_order_id')->count(),
                    'with_tax_rate' => ProductiveExpense::whereNotNull('tax_rate_id')->count(),
                    'with_attachment' => ProductiveExpense::whereNotNull('attachment_id')->count(),
                ],
                'integrations' => [
                    'total' => ProductiveIntegration::count(),
                    'with_subsidiary' => ProductiveIntegration::whereNotNull('subsidiary_id')->count(),
                    'with_project' => ProductiveIntegration::whereNotNull('project_id')->count(),
                    'with_creator' => ProductiveIntegration::whereNotNull('creator_id')->count(),
                    'with_deal' => ProductiveIntegration::whereNotNull('deal_id')->count(),
                ],
                'pages' => [
                    'total' => ProductivePage::count(),
                    'with_creator' => ProductivePage::whereNotNull('creator_id')->count(),
                    'with_project' => ProductivePage::whereNotNull('project_id')->count(),
                    'with_attachment' => ProductivePage::whereNotNull('attachment_id')->count(),
                ],
                'sections' => [
                    'total' => ProductiveSection::count(),
                    'with_deal' => ProductiveSection::whereNotNull('deal_id')->count(),
                ],
                
                'custom_domains' => [
                    'total' => ProductiveCustomDomain::count(),
                    'with_subsidiary' => ProductiveCustomDomain::whereNotNull('subsidiary_id')->count(),
                ],
                'timesheets' => [
                    'total' => ProductiveTimesheet::count(),
                    'with_person' => ProductiveTimesheet::whereNotNull('person_id')->count(),
                    'with_creator' => ProductiveTimesheet::whereNotNull('creator_id')->count(),
                ],
                'workflow_statuses' => [
                    'total' => ProductiveWorkflowStatus::count(),
                    'with_workflow' => ProductiveWorkflowStatus::whereNotNull('workflow_id')->count(),
                ],
                'tags' => [
                    'total' => ProductiveTag::count(),
                ],
                'service_types' => [
                    'total' => ProductiveServiceType::count(),
                    'with_assignee' => ProductiveServiceType::whereNotNull('assignee_id')->count(),
                ],
                'services' => [
                    'total' => ProductiveService::count(),
                    'with_service_type' => ProductiveService::whereNotNull('service_type_id')->count(),
                    'with_deal' => ProductiveService::whereNotNull('deal_id')->count(),
                    'with_person' => ProductiveService::whereNotNull('person_id')->count(),
                    'with_section' => ProductiveService::whereNotNull('section_id')->count(),
                ],
                
                'task_lists' => [
                    'total' => ProductiveTaskList::count(),
                    'with_project' => ProductiveTaskList::whereNotNull('project_id')->count(),
                    'with_board' => ProductiveTaskList::whereNotNull('board_id')->count(),
                ],
                'tasks' => [
                    'total' => ProductiveTask::count(),
                    'with_project' => ProductiveTask::whereNotNull('project_id')->count(),
                    'with_creator' => ProductiveTask::whereNotNull('creator_id')->count(),
                    'with_assignee' => ProductiveTask::whereNotNull('assignee_id')->count(),
                    'with_last_actor' => ProductiveTask::whereNotNull('last_actor_id')->count(),
                    'with_task_list' => ProductiveTask::whereNotNull('task_list_id')->count(),
                    'with_parent_task' => ProductiveTask::whereNotNull('parent_task_id')->count(),
                    'with_workflow_status' => ProductiveTask::whereNotNull('workflow_status_id')->count(),
                    'with_attachment' => ProductiveTask::whereNotNull('attachment_id')->count(),
                ],
                'todos' => [
                    'total' => ProductiveTodo::count(),
                    'with_assignee' => ProductiveTodo::whereNotNull('assignee_id')->count(),
                    'with_deal' => ProductiveTodo::whereNotNull('deal_id')->count(),
                    'with_task' => ProductiveTodo::whereNotNull('task_id')->count(),
                ],
                'payment_reminder_sequences' => [
                    'total' => ProductivePrs::count(),
                    'with_creator' => ProductivePrs::whereNotNull('creator_id')->count(),
                    'with_updater' => ProductivePrs::whereNotNull('updater_id')->count(),
                    'with_payment_reminder' => ProductivePrs::whereNotNull('payment_reminder_id')->count(),
                ],
                'payment_reminders' => [
                    'total' => ProductivePaymentReminder::count(),
                    'with_creator' => ProductivePaymentReminder::whereNotNull('creator_id')->count(),
                    'with_updater' => ProductivePaymentReminder::whereNotNull('updater_id')->count(),
                    'with_invoice' => ProductivePaymentReminder::whereNotNull('invoice_id')->count(),
                    'with_prs' => ProductivePaymentReminder::whereNotNull('prs_id')->count(),
                ],
                'custom_fields' => [
                    'total' => ProductiveCustomField::count(),
                    'with_project' => ProductiveCustomField::whereNotNull('project_id')->count(),
                    'with_section' => ProductiveCustomField::whereNotNull('section_id')->count(),
                    'with_survey' => ProductiveCustomField::whereNotNull('survey_id')->count(),
                    'with_person' => ProductiveCustomField::whereNotNull('person_id')->count(),
                    'with_cfo' => ProductiveCustomField::whereNotNull('cfo_id')->count(),
                ],
                'custom_field_options' => [
                    'total' => ProductiveCfo::count(),
                    'with_custom_field' => ProductiveCfo::whereNotNull('custom_field_id')->count(),
                ],
                'time_entries' => [
                    'total' => ProductiveTimeEntry::count(),
                    'with_person' => ProductiveTimeEntry::whereNotNull('person_id')->count(),
                    'with_service' => ProductiveTimeEntry::whereNotNull('service_id')->count(),
                    'with_task' => ProductiveTimeEntry::whereNotNull('task_id')->count(),
                    'with_deal' => ProductiveTimeEntry::whereNotNull('deal_id')->count(),
                    'with_approver' => ProductiveTimeEntry::whereNotNull('approver_id')->count(),
                    'with_updater' => ProductiveTimeEntry::whereNotNull('updater_id')->count(),
                    'with_rejecter' => ProductiveTimeEntry::whereNotNull('rejecter_id')->count(),
                    'with_creator' => ProductiveTimeEntry::whereNotNull('creator_id')->count(),
                    'with_last_actor' => ProductiveTimeEntry::whereNotNull('last_actor_id')->count(),
                    'with_person_subsidiary' => ProductiveTimeEntry::whereNotNull('person_subsidiary_id')->count(),
                    'with_deal_subsidiary' => ProductiveTimeEntry::whereNotNull('deal_subsidiary_id')->count(),
                    'with_timesheet' => ProductiveTimeEntry::whereNotNull('timesheet_id')->count(),
                ],
                'time_entry_versions' => [
                    'total' => ProductiveTimeEntry::count(),
                    'with_creator' => ProductiveTimeEntry::whereNotNull('creator_id')->count(),
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
                'people' => $stats['people'],
                'workflows' => $stats['workflows'],
                'document_types' => $stats['document_types'],
                'subsidiaries' => $stats['subsidiaries'],
                'tax_rates' => $stats['tax_rates'],
                'document_styles' => $stats['document_styles'],
                'pipelines' => $stats['pipelines'],
                'deal_statuses' => $stats['deal_statuses'],
                'lost_reasons' => $stats['lost_reasons'],
                'contracts' => $stats['contracts'],
                'purchase_orders' => $stats['purchase_orders'],
                'contact_entries' => $stats['contact_entries'],
                'approval_policies' => $stats['approval_policies'],
                'approval_policy_assignments' => $stats['approval_policy_assignments'],
                'deals' => $stats['deals'],
                'emails' => $stats['emails'],
                'bills' => $stats['bills'],
                'attachments' => $stats['attachments'],
                'teams' => $stats['teams'],
                'invoices' => $stats['invoices'],
                'invoice_attributions' => $stats['invoice_attributions'],
                'boards' => $stats['boards'],
                'bookings' => $stats['bookings'],
                'comments' => $stats['comments'],
                'discussions' => $stats['discussions'],
                'events' => $stats['events'],
                'expenses' => $stats['expenses'],
                'integrations' => $stats['integrations'],
                'pages' => $stats['pages'],
                'sections' => $stats['sections'],
                'custom_domains' => $stats['custom_domains'],
                'timesheets' => $stats['timesheets'],
                'workflow_statuses' => $stats['workflow_statuses'],
                'tags' => $stats['tags'],
                'service_types' => $stats['service_types'],
                'services' => $stats['services'],
                'task_lists' => $stats['task_lists'],
                'tasks' => $stats['tasks'],
                'todos' => $stats['todos'],
                'payment_reminder_sequences' => $stats['payment_reminder_sequences'],
                'payment_reminders' => $stats['payment_reminders'],
                'custom_fields' => $stats['custom_fields'],
                'custom_field_options' => $stats['custom_field_options'],
                'time_entries' => $stats['time_entries'],
                'time_entry_versions' => $stats['time_entry_versions'],
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Error getting relationship statistics: ' . $e->getMessage());
            }
            // Return basic stats if detailed stats fail
            return [
                'companies' => ['total' => ProductiveCompany::count()],
                'projects' => ['total' => ProductiveProject::count()],
                'people' => ['total' => ProductivePeople::count()],
                'workflows' => ['total' => ProductiveWorkflow::count()],
                'document_types' => ['total' => ProductiveDocumentType::count()],
                'subsidiaries' => ['total' => ProductiveSubsidiary::count()],
                'tax_rates' => ['total' => ProductiveTaxRate::count()],
                'document_styles' => ['total' => ProductiveDocumentStyle::count()],
                'pipelines' => ['total' => ProductivePipeline::count()],
                'deal_statuses' => ['total' => ProductiveDealStatus::count()],
                'lost_reasons' => ['total' => ProductiveLostReason::count()],
                'contracts' => ['total' => ProductiveContract::count()],
                'purchase_orders' => ['total' => ProductivePurchaseOrder::count()],
                'contact_entries' => ['total' => ProductiveContactEntry::count()],
                'approval_policies' => ['total' => ProductiveApprovalPolicy::count()],
                'approval_policy_assignments' => ['total' => ProductiveApa::count()],
                'deals' => ['total' => ProductiveDeal::count()],
                'emails' => ['total' => ProductiveEmail::count()],
                'bills' => ['total' => ProductiveBill::count()],
                'attachments' => ['total' => ProductiveAttachment::count()],
                'teams' => ['total' => ProductiveTeam::count()],
                'invoices' => ['total' => ProductiveInvoice::count()],
                'invoice_attributions' => ['total' => ProductiveInvoiceAttribution::count()],
                'boards' => ['total' => ProductiveBoard::count()],  
                'bookings' => ['total' => ProductiveBooking::count()],
                'comments' => ['total' => ProductiveComment::count()],
                'discussions' => ['total' => ProductiveDiscussion::count()],
                'events' => ['total' => ProductiveEvent::count()],
                'expenses' => ['total' => ProductiveExpense::count()],
                'integrations' => ['total' => ProductiveIntegration::count()],
                'pages' => ['total' => ProductivePage::count()],
                'sections' => ['total' => ProductiveSection::count()],
                'custom_domains' => ['total' => ProductiveCustomDomain::count()],
                'timesheets' => ['total' => ProductiveTimesheet::count()],
                'workflow_statuses' => ['total' => ProductiveWorkflowStatus::count()],
                'tags' => ['total' => ProductiveTag::count()],
                'service_types' => ['total' => ProductiveServiceType::count()],
                'services' => ['total' => ProductiveService::count()],
                'task_lists' => ['total' => ProductiveTaskList::count()],
                'tasks' => ['total' => ProductiveTask::count()],
                'todos' => ['total' => ProductiveTodo::count()],
                'payment_reminder_sequences' => ['total' => ProductivePrs::count()],
                'payment_reminders' => ['total' => ProductivePaymentReminder::count()],
                'custom_fields' => ['total' => ProductiveCustomField::count()],
                'custom_field_options' => ['total' => ProductiveCfo::count()],
                'time_entries' => ['total' => ProductiveTimeEntry::count()],
                'time_entry_versions' => ['total' => ProductiveTimeEntryVersion::count()],
            ];
        }
    }
}
