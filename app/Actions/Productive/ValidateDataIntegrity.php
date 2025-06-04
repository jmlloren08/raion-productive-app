<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductivePeople;
use App\Models\ProductiveWorkflow;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductiveDocumentType;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveTaxRate;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductiveDealStatus;
use App\Models\ProductiveLostReason;
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
// use App\Models\ProductiveActivity;
use App\Models\ProductiveBoard;
use App\Models\ProductiveBooking;
use App\Models\ProductiveComment;
use App\Models\ProductiveDiscussion;
use App\Models\ProductiveEvent;
use App\Models\ProductiveExpense;
use App\Models\ProductiveIntegration;
use App\Models\ProductivePage;
use App\Models\ProductiveSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ValidateDataIntegrity extends AbstractAction
{
    /**
     * Execute the action to validate data integrity.
     *
     * @param array $parameters
     * @return bool
     */
    public function handle(array $parameters = []): bool
    {
        $command = $parameters['command'] ?? null;

        try {
            if ($command instanceof Command) {
                $command->info('Validating data integrity...');
            }

            // Get statistics for each model
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
                // 'activities' => [
                //     'total' => ProductiveActivity::count(),
                //     'with_creator' => ProductiveActivity::whereNotNull('creator_id')->count(),
                //     'with_comment' => ProductiveActivity::whereNotNull('comment_id')->count(),
                //     'with_email' => ProductiveActivity::whereNotNull('email_id')->count(),
                //     'with_attachment' => ProductiveActivity::whereNotNull('attachment_id')->count(),
                // ],
            ];

            // Output report
            if ($command instanceof Command) {
                $command->info('Data Integrity Report:');
                $command->info('=====================');

                // Companies
                $command->info("\nCompanies:");
                $command->info("- Total: {$stats['companies']['total']}");
                $command->info("- With Default Subsidiary: {$stats['companies']['with_default_subsidiary']}");
                $command->info("- With Default Tax Rate: {$stats['companies']['with_default_tax_rate']}");

                // Projects
                $command->info("\nProjects:");
                $command->info("- Total: {$stats['projects']['total']}");
                $command->info("- With Company: {$stats['projects']['with_company']}");
                $command->info("- With Project Manager: {$stats['projects']['with_project_manager']}");
                $command->info("- With Last Actor: {$stats['projects']['with_last_actor']}");
                $command->info("- With Workflow: {$stats['projects']['with_workflow']}");

                // People
                $command->info("\nPeople:");
                $command->info("- Total: {$stats['people']['total']}");
                $command->info("- With Manager: {$stats['people']['with_manager']}");
                $command->info("- With Company: {$stats['people']['with_company']}");
                $command->info("- With Subsidiary: {$stats['people']['with_subsidiary']}");
                $command->info("- With APA: {$stats['people']['with_apa_id']}");
                $command->info("- With Team: {$stats['people']['with_team_id']}");

                // Workflows
                $command->info("\nWorkflows:");
                $command->info("- Total: {$stats['workflows']['total']}");
                $command->info("- With Workflow Status: {$stats['workflows']['with_workflow_status']}");

                // Document Types
                $command->info("\nDocument Types:");
                $command->info("- Total: {$stats['document_types']['total']}");
                $command->info("- With Subsidiary: {$stats['document_types']['with_subsidiary']}");
                $command->info("- With Document Style: {$stats['document_types']['with_document_style']}");
                $command->info("- With Attachment: {$stats['document_types']['with_attachment']}");

                // Contact Entries
                $command->info("\nContact Entries:");
                $command->info("- Total: {$stats['contact_entries']['total']}");
                $command->info("- With Company: {$stats['contact_entries']['with_company']}");
                $command->info("- With Person: {$stats['contact_entries']['with_person']}");
                $command->info("- With Invoice: {$stats['contact_entries']['with_invoice']}");
                $command->info("- With Subsidiary: {$stats['contact_entries']['with_subsidiary']}");
                $command->info("- With Purchase Order: {$stats['contact_entries']['with_purchase_order']}");

                // Subsidiaries
                $command->info("\nSubsidiaries:");
                $command->info("- Total: {$stats['subsidiaries']['total']}");
                $command->info("- With Contact Entry: {$stats['subsidiaries']['with_contact_entry']}");
                $command->info("- With Custom Domain: {$stats['subsidiaries']['with_custom_domain']}");
                $command->info("- With Tax Rate: {$stats['subsidiaries']['with_tax_rate']}");
                $command->info("- With Integration: {$stats['subsidiaries']['with_integration']}");

                // Tax Rates
                $command->info("\nTax Rates:");
                $command->info("- Total: {$stats['tax_rates']['total']}");
                $command->info("- With Subsidiary: {$stats['tax_rates']['with_subsidiary']}");

                // Document Styles
                $command->info("\nDocument Styles:");
                $command->info("- Total: {$stats['document_styles']['total']}");
                $command->info("- With Attachment: {$stats['document_styles']['with_attachment']}");

                // Deal Statuses
                $command->info("\nDeal Statuses:");
                $command->info("- Total: {$stats['deal_statuses']['total']}");
                $command->info("- With Pipeline: {$stats['deal_statuses']['with_pipeline']}");

                // Lost Reasons
                $command->info("\nLost Reasons:");
                $command->info("- Total: {$stats['lost_reasons']['total']}");

                // Contracts
                $command->info("\nContracts:");
                $command->info("- Total: {$stats['contracts']['total']}");
                $command->info("- With Deal: {$stats['contracts']['with_deal']}");

                // Approval Policy Assignments
                $command->info("\nApproval Policy Assignments:");
                $command->info("- Total: {$stats['approval_policy_assignments']['total']}");
                $command->info("- With Person: {$stats['approval_policy_assignments']['with_person']}");
                $command->info("- With Deal: {$stats['approval_policy_assignments']['with_deal']}");
                $command->info("- With Approval Policy: {$stats['approval_policy_assignments']['with_approval_policy']}");

                // Deals
                $command->info("\nDeals:");
                $command->info("- Total: {$stats['deals']['total']}");
                $command->info("- With Creator: {$stats['deals']['with_creator']}");
                $command->info("- With Company: {$stats['deals']['with_company']}");
                $command->info("- With Document Type: {$stats['deals']['with_document_type']}");
                $command->info("- With Responsible Person: {$stats['deals']['with_responsible_person']}");
                $command->info("- With Deal Status: {$stats['deals']['with_deal_status']}");
                $command->info("- With Project: {$stats['deals']['with_project']}");
                $command->info("- With Lost Reason: {$stats['deals']['with_lost_reason']}");
                $command->info("- With Contract: {$stats['deals']['with_contract']}");
                $command->info("- With Contact: {$stats['deals']['with_contact']}");
                $command->info("- With Subsidiary: {$stats['deals']['with_subsidiary']}");
                $command->info("- With Tax Rate: {$stats['deals']['with_tax_rate']}");
                $command->info("- With APA: {$stats['deals']['with_apa']}");

                // Purchase Orders
                $command->info("\nPurchase Orders:");
                $command->info("- Total: {$stats['purchase_orders']['total']}");
                $command->info("- With Deal: {$stats['purchase_orders']['with_deal']}");
                $command->info("- With Creator: {$stats['purchase_orders']['with_creator']}");
                $command->info("- With Document Type: {$stats['purchase_orders']['with_document_type']}");
                $command->info("- With Attachment: {$stats['purchase_orders']['with_attachment']}");
                $command->info("- With Bill To: {$stats['purchase_orders']['with_bill_to']}");
                $command->info("- With Bill From: {$stats['purchase_orders']['with_bill_from']}");

                // Approval Policies
                $command->info("\nApproval Policies:");
                $command->info("Total: {$stats['approval_policies']['total']}");

                // Pipelines
                $command->info("\nPipelines:");
                $command->info("- Total: {$stats['pipelines']['total']}");
                $command->info("- With Creator: {$stats['pipelines']['with_creator']}");
                $command->info("- With Updater: {$stats['pipelines']['with_updater']}");

                // Emails
                $command->info("\nEmails:");
                $command->info("- Total: {$stats['emails']['total']}");
                $command->info("- With Creator: {$stats['emails']['with_creator']}");
                $command->info("- With Deal: {$stats['emails']['with_deal']}");
                $command->info("- With Invoice: {$stats['emails']['with_invoice']}");
                $command->info("- With Payment Reminder Sequence: {$stats['emails']['with_payment_reminder_sequence']}");
                $command->info("- With Attachment: {$stats['emails']['with_attachment']}");

                // Bills
                $command->info("\nBills:");
                $command->info("- Total: {$stats['bills']['total']}");
                $command->info("- With Purchase Order: {$stats['bills']['with_purchase_order']}");
                $command->info("- With Creator: {$stats['bills']['with_creator']}");
                $command->info("- With Deal: {$stats['bills']['with_deal']}");
                $command->info("- With Attachment: {$stats['bills']['with_attachment']}");

                // Attachments
                $command->info("\nAttachments:");
                $command->info("- Total: {$stats['attachments']['total']}");
                $command->info("- With Creator: {$stats['attachments']['with_creator']}");
                $command->info("- With Invoice: {$stats['attachments']['with_invoice']}");
                $command->info("- With Purchase Order: {$stats['attachments']['with_purchase_order']}");
                $command->info("- With Bill: {$stats['attachments']['with_bill']}");
                $command->info("- With Email: {$stats['attachments']['with_email']}");
                $command->info("- With Page: {$stats['attachments']['with_page']}");
                $command->info("- With Expense: {$stats['attachments']['with_expense']}");
                $command->info("- With Comment: {$stats['attachments']['with_comment']}");
                $command->info("- With Task: {$stats['attachments']['with_task']}");
                $command->info("- With Document Style: {$stats['attachments']['with_document_style']}");
                $command->info("- With Document Type: {$stats['attachments']['with_document_type']}");
                $command->info("- With Deal: {$stats['attachments']['with_deal']}");

                // Teams
                $command->info("\nTeams:");
                $command->info("- Total: {$stats['teams']['total']}");

                // Invoices
                $command->info("\nInvoices:");
                $command->info("- Total: {$stats['invoices']['total']}");
                $command->info("- With Bill To: {$stats['invoices']['with_bill_to']}");
                $command->info("- With Bill From: {$stats['invoices']['with_bill_from']}");
                $command->info("- With Company: {$stats['invoices']['with_company']}");
                $command->info("- With Document Type: {$stats['invoices']['with_document_type']}");
                $command->info("- With Subsidiary: {$stats['invoices']['with_subsidiary']}");
                $command->info("- With Parent Invoice: {$stats['invoices']['with_parent_invoice']}");
                $command->info("- With Issuer: {$stats['invoices']['with_issuer']}");
                $command->info("- With Invoice Attribution: {$stats['invoices']['with_invoice_attribution']}");
                $command->info("- With Attachment: {$stats['invoices']['with_attachment']}");

                // Invoice Attributions
                $command->info("\nInvoice Attributions:");
                $command->info("- Total: {$stats['invoice_attributions']['total']}");
                $command->info("- With Invoice: {$stats['invoice_attributions']['with_invoice']}");
                $command->info("- With Budget: {$stats['invoice_attributions']['with_budget']}");

                // Boards
                $command->info("\nBoards:");
                $command->info("- Total: {$stats['boards']['total']}");
                $command->info("- With Project: {$stats['boards']['with_project']}");

                // Bookings
                $command->info("\nBookings:");
                $command->info("Total: {$stats['bookings']['total']}");
                $command->info("With Service: {$stats['bookings']['with_service']}");
                $command->info("With Event: {$stats['bookings']['with_event']}");
                $command->info("With Person: {$stats['bookings']['with_person']}");
                $command->info("With Creator: {$stats['bookings']['with_creator']}");
                $command->info("With Updater: {$stats['bookings']['with_updater']}");
                $command->info("With Approver: {$stats['bookings']['with_approver']}");
                $command->info("With Rejecter: {$stats['bookings']['with_rejecter']}");
                $command->info("With Canceler: {$stats['bookings']['with_canceler']}");
                $command->info("With Origin: {$stats['bookings']['with_origin']}");
                $command->info("With Approval Status: {$stats['bookings']['with_approval_status']}");
                $command->info("With Attachment: {$stats['bookings']['with_attachment']}");

                // Comments
                $command->info("\nComments:");
                $command->info("- Total: {$stats['comments']['total']}");
                $command->info("- With Company: {$stats['comments']['with_company']}");
                $command->info("- With Creator: {$stats['comments']['with_creator']}");
                $command->info("- With Deal: {$stats['comments']['with_deal']}");
                $command->info("- With Discussion: {$stats['comments']['with_discussion']}");
                $command->info("- With Invoice: {$stats['comments']['with_invoice']}");
                $command->info("- With Person: {$stats['comments']['with_person']}");
                $command->info("- With Pinned By: {$stats['comments']['with_pinned_by']}");
                $command->info("- With Task: {$stats['comments']['with_task']}");
                $command->info("- With Purchase Order: {$stats['comments']['with_purchase_order']}");
                $command->info("- With Attachment: {$stats['comments']['with_attachment']}");

                // Discussions
                $command->info("\nDiscussions:");
                $command->info("- Total: {$stats['discussions']['total']}");
                $command->info("- With Page: {$stats['discussions']['with_page']}");

                // Events
                $command->info("\nEvents:");
                $command->info("- Total: {$stats['events']['total']}");

                // Expenses
                $command->info("\nExpenses:");
                $command->info("- Total: {$stats['expenses']['total']}");
                $command->info("- With Deal: {$stats['expenses']['with_deal']}");
                $command->info("- With Service Type: {$stats['expenses']['with_service_type']}");
                $command->info("- With Person: {$stats['expenses']['with_person']}");
                $command->info("- With Creator: {$stats['expenses']['with_creator']}");
                $command->info("- With Approver: {$stats['expenses']['with_approver']}");
                $command->info("- With Rejecter: {$stats['expenses']['with_rejecter']}");
                $command->info("- With Service: {$stats['expenses']['with_service']}");
                $command->info("- With Purchase Order: {$stats['expenses']['with_purchase_order']}");
                $command->info("- With Tax Rate: {$stats['expenses']['with_tax_rate']}");
                $command->info("- With Attachment: {$stats['expenses']['with_attachment']}");

                // Integrations
                $command->info("\nIntegrations:");
                $command->info("- Total: {$stats['integrations']['total']}");
                $command->info("- With Subsidiary: {$stats['integrations']['with_subsidiary']}");
                $command->info("- With Project: {$stats['integrations']['with_project']}");
                $command->info("- With Creator: {$stats['integrations']['with_creator']}");
                $command->info("- With Deal: {$stats['integrations']['with_deal']}");

                // Pages
                $command->info("\nPages:");
                $command->info("- Total: {$stats['pages']['total']}");
                $command->info("- With Creator: {$stats['pages']['with_creator']}");
                $command->info("- With Project: {$stats['pages']['with_project']}");
                $command->info("- With Attachment: {$stats['pages']['with_attachment']}");

                // Sections
                $command->info("\nSections:");
                $command->info("- Total: {$stats['sections']['total']}");
                $command->info("- With Deal: {$stats['sections']['with_deal']}");

                // Activities
                // $command->info("\nActivities:");
                // $command->info("- Total: {$stats['activities']['total']}");
                // $command->info("- With Creator: {$stats['activities']['with_creator']}");
                // $command->info("- With Comment: {$stats['activities']['with_comment']}");
                // $command->info("- With Email: {$stats['activities']['with_email']}");
                // $command->info("- With Attachment: {$stats['activities']['with_attachment']}");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Error validating data integrity: ' . $e->getMessage());
            }
            Log::error("Error validating data integrity: " . $e->getMessage());
            return false;
        }
    }
}
