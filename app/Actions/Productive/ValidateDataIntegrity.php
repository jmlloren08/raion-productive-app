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
use Illuminate\Console\Command;

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
                    'with_subsidiary' => ProductiveCompany::whereNotNull('default_subsidiary_id')->count(),
                    'with_tax_rate' => ProductiveCompany::whereNotNull('default_tax_rate_id')->count(),
                ],
                'projects' => [
                    'total' => ProductiveProject::count(),
                    'with_company' => ProductiveProject::whereNotNull('company_id')->count(),
                    'with_project_manager' => ProductiveProject::whereNotNull('project_manager_id')->count(),
                    'with_last_actor' => ProductiveProject::whereNotNull('last_actor_id')->count(),
                    'with_workflow' => ProductiveProject::whereNotNull('workflow_id')->count()
                ],
                'people' => [
                    'total' => ProductivePeople::count(),
                    'with_manager' => ProductivePeople::whereNotNull('manager_id')->count(),
                    'with_company' => ProductivePeople::whereNotNull('company_id')->count(),
                    'with_subsidiary' => ProductivePeople::whereNotNull('subsidiary_id')->count(),
                    'with_apa_id' => ProductivePeople::whereNotNull('apa_id')->count(),
                    'with_team_id' => ProductivePeople::whereNotNull('team_id')->count()
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
                'contact_entries' => [
                    'total' => ProductiveContactEntry::count(),
                    'with_company' => ProductiveContactEntry::whereNotNull('company_id')->count(),
                    'with_person' => ProductiveContactEntry::whereNotNull('person_id')->count(),
                    'with_invoice' => ProductiveContactEntry::whereNotNull('invoice_id')->count(),
                    'with_subsidiary' => ProductiveContactEntry::whereNotNull('subsidiary_id')->count(),
                    'with_purchase_order' => ProductiveContactEntry::whereNotNull('purchase_order_id')->count()
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
            ];

            // Output report
            if ($command instanceof Command) {
                $command->info('Data Integrity Report:');
                $command->info('=====================');

                // Companies
                $command->info("\nCompanies:");
                $command->info("- Total: {$stats['companies']['total']}");
                $command->info("- With Subsidiary: {$stats['companies']['with_subsidiary']}");
                $command->info("- With Tax Rate: {$stats['companies']['with_tax_rate']}");

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
            }

            // Validate relationships
            $warnings = [];

            if ($command instanceof Command) {
                $this->validateRelationships($command);
            }

            // Check for deals with invalid lost reason relationships
            $invalidLostReasonDeals = ProductiveDeal::whereNotNull('lost_reason_id')
                ->whereNotIn('lost_reason_id', ProductiveLostReason::pluck('id'))
                ->count();
            if ($invalidLostReasonDeals > 0) {
                $warnings[] = "Found {$invalidLostReasonDeals} deals with invalid lost reason relationships";
            }
            // Output warnings if any
            if (!empty($warnings)) {
                if ($command instanceof Command) {
                    $command->warn("\nWarnings:");
                    foreach ($warnings as $warning) {
                        $command->warn("- {$warning}");
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Error validating data integrity: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function validateRelationships(Command $command): void
    {
        // Document Style relationships
        $documentStylesWithoutAttachment = ProductiveDocumentStyle::whereDoesntHave('attachment')->whereNotNull('attachment_id')->count();
        if ($documentStylesWithoutAttachment > 0) {
            $command->warn("Found {$documentStylesWithoutAttachment} document styles with invalid attachment relationships");
        }

        // Tax Rate relationships
        $taxRatesWithoutSubsidiary = ProductiveTaxRate::whereDoesntHave('subsidiary')->whereNotNull('subsidiary_id')->count();
        if ($taxRatesWithoutSubsidiary > 0) {
            $command->warn("Found {$taxRatesWithoutSubsidiary} tax rates with invalid subsidiary relationships");
        }

        // Subsidiary relationships
        $subsidiariesWithInvalidBillFrom = ProductiveSubsidiary::whereDoesntHave('contactEntry')->whereNotNull('contact_entry_id')->count();
        if ($subsidiariesWithInvalidBillFrom > 0) {
            $command->warn("Found {$subsidiariesWithInvalidBillFrom} subsidiaries with invalid bill_from relationships");
        }

        $subsidiariesWithInvalidCustomDomain = ProductiveSubsidiary::whereDoesntHave('customDomain')->whereNotNull('custom_domain_id')->count();
        if ($subsidiariesWithInvalidCustomDomain > 0) {
            $command->warn("Found {$subsidiariesWithInvalidCustomDomain} subsidiaries with invalid custom_domain relationships");
        }

        $subsidiariesWithInvalidTaxRate = ProductiveSubsidiary::whereDoesntHave('defaultTaxRate')->whereNotNull('default_tax_rate_id')->count();
        if ($subsidiariesWithInvalidTaxRate > 0) {
            $command->warn("Found {$subsidiariesWithInvalidTaxRate} subsidiaries with invalid tax_rate relationships");
        }

        $subsidiariesWithInvalidIntegration = ProductiveSubsidiary::whereDoesntHave('integration')->whereNotNull('integration_id')->count();
        if ($subsidiariesWithInvalidIntegration > 0) {
            $command->warn("Found {$subsidiariesWithInvalidIntegration} subsidiaries with invalid integration relationships");
        }

        // Projects with company relationships
        $projectsWithoutCompany = ProductiveProject::whereDoesntHave('company')->count();
        if ($projectsWithoutCompany > 0) {
            $command->warn("Found {$projectsWithoutCompany} projects without company relationships");
        }

        // Document Types with subsidiary relationships
        $documentTypesWithoutSubsidiary = ProductiveDocumentType::whereDoesntHave('subsidiary')->count();
        if ($documentTypesWithoutSubsidiary > 0) {
            $command->warn("Found {$documentTypesWithoutSubsidiary} document types without subsidiary relationships");
        }

        // Contact Entries with company relationships
        $contactEntriesWithoutCompany = ProductiveContactEntry::whereDoesntHave('company')->count();
        if ($contactEntriesWithoutCompany > 0) {
            $command->warn("Found {$contactEntriesWithoutCompany} contact entries without company relationships");
        }

        // Contact Entries with person relationships
        $contactEntriesWithoutPerson = ProductiveContactEntry::whereDoesntHave('person')->count();
        if ($contactEntriesWithoutPerson > 0) {
            $command->warn("Found {$contactEntriesWithoutPerson} contact entries without person relationships");
        }

        // Deals with company relationships
        $dealsWithoutCompany = ProductiveDeal::whereDoesntHave('company')->count();
        if ($dealsWithoutCompany > 0) {
            $command->warn("Found {$dealsWithoutCompany} deals without company relationships");
        }

        // Deals with project relationships
        $dealsWithoutProject = ProductiveDeal::whereDoesntHave('project')->count();
        if ($dealsWithoutProject > 0) {
            $command->warn("Found {$dealsWithoutProject} deals without project relationships");
        }

        // Deals with lost reason relationships
        $dealsWithoutLostReason = ProductiveDeal::whereDoesntHave('lostReason')->whereNotNull('lost_reason_id')->count();
        if ($dealsWithoutLostReason > 0) {
            $command->warn("Found {$dealsWithoutLostReason} deals with invalid lost reason relationships");
        }

        // Contracts with deal relationships
        $contractsWithoutDeal = ProductiveContract::whereDoesntHave('deal')->whereNotNull('deal_id')->count();
        if ($contractsWithoutDeal > 0) {
            $command->warn("Found {$contractsWithoutDeal} contracts with invalid deal relationships");
        }
    }
}
