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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

            // Document Style Statistics
            $totalDocumentStyles = ProductiveDocumentStyle::count();
            $documentStylesWithAttachment = ProductiveDocumentStyle::whereNotNull('attachment_id')->count();

            // Tax Rate Statistics
            $totalTaxRates = ProductiveTaxRate::count();
            $taxRatesWithSubsidiary = ProductiveTaxRate::whereNotNull('subsidiary_id')->count();

            // Subsidiary Statistics
            $totalSubsidiaries = ProductiveSubsidiary::count();
            $subsidiariesWithBillFrom = ProductiveSubsidiary::whereNotNull('contact_entry_id')->count();
            $subsidiariesWithCustomDomain = ProductiveSubsidiary::whereNotNull('custom_domain_id')->count();
            $subsidiariesWithTaxRate = ProductiveSubsidiary::whereNotNull('default_tax_rate_id')->count();
            $subsidiariesWithIntegration = ProductiveSubsidiary::whereNotNull('integration_id')->count();

            // Company Statistics
            $totalCompanies = ProductiveCompany::count();
            $companiesWithSubsidiary = ProductiveCompany::whereNotNull('default_subsidiary_id')->count();
            $companiesWithTaxRate = ProductiveCompany::whereNotNull('default_tax_rate_id')->count();

            // People Statistics
            $totalPeople = ProductivePeople::count();
            $peopleWithManager = ProductivePeople::whereNotNull('manager_id')->count();
            $peopleWithCompany = ProductivePeople::whereNotNull('company_id')->count();
            $peopleWithSubsidiary = ProductivePeople::whereNotNull('subsidiary_id')->count();
            $peopleWithApa = ProductivePeople::whereNotNull('apa_id')->count();
            $peopleWithTeam = ProductivePeople::whereNotNull('team_id')->count();

            // Workflow Statistics
            $totalWorkflows = ProductiveWorkflow::count();
            $workflowWithWS = ProductiveWorkflow::whereNotNull('workflow_status_id')->count();

            // Project Statistics
            $totalProjects = ProductiveProject::count();
            $projectsWithCompany = ProductiveProject::whereNotNull('company_id')->count();
            $projectsWithProjectManager = ProductiveProject::whereNotNull('project_manager_id')->count();
            $projectsWithLastActor = ProductiveProject::whereNotNull('last_actor_id')->count();
            $projectsWithWorkflow = ProductiveProject::whereNotNull('workflow_id')->count();

            // Deal Statistics
            $totalDeals = ProductiveDeal::count();
            $dealsWithSubsidiary = ProductiveDeal::whereNotNull('subsidiary_id')->count();
            $dealsWithCreator = ProductiveDeal::whereNotNull('creator_id')->count();
            $dealsWithCompany = ProductiveDeal::whereNotNull('company_id')->count();
            $dealsWithDT = ProductiveDeal::whereNotNull('document_type_id')->count();
            $dealsWithResponsible = ProductiveDeal::whereNotNull('responsible_id')->count();
            $dealsWithDealStatus = ProductiveDeal::whereNotNull('deal_status_id')->count();
            $dealsWithProject = ProductiveDeal::whereNotNull('project_id')->count();
            $dealsWithLostReason = ProductiveDeal::whereNotNull('lost_reason_id')->count();
            $dealsWithContract = ProductiveDeal::whereNotNull('contract_id')->count();
            $dealsWithContact = ProductiveDeal::whereNotNull('contact_id')->count();
            $dealsWithSubsidiary = ProductiveDeal::whereNotNull('subsidiary_id')->count();
            $dealsWithTaxRate = ProductiveDeal::whereNotNull('tax_rate_id')->count();
            $dealsWithApa = ProductiveDeal::whereNotNull('apa_id')->count();

            // Document Type Statistics
            $totalDocumentTypes = ProductiveDocumentType::count();
            $documentTypesWithSubsidiary = ProductiveDocumentType::whereNotNull('subsidiary_id')->count();
            $documentTypesWithDS = ProductiveDocumentType::whereNotNull('document_style_id')->count();
            $documentTypesWithAttachment = ProductiveDocumentType::whereNotNull('attachment_id')->count();

            // Contact Entry Statistics
            $totalContactEntries = ProductiveContactEntry::count();
            $contactEntriesWithCompany = ProductiveContactEntry::whereNotNull('company_id')->count();
            $contactEntriesWithPeople = ProductiveContactEntry::whereNotNull('person_id')->count();
            $contactEntriesWithInvoice = ProductiveContactEntry::whereNotNull('invoice_id')->count();
            $contactEntriesWithSubsidiary = ProductiveContactEntry::whereNotNull('subsidiary_id')->count();
            $contactWithPO = ProductiveContactEntry::whereNotNull('purchase_order_id')->count();

            if ($command instanceof Command) {
                $command->info('=== Data Integrity Report ===');

                $command->info('Document Styles:');
                $command->info("  Total: {$totalDocumentStyles}");
                $command->info("  With Attachment: {$documentStylesWithAttachment}");

                $command->info('Tax Rates:');
                $command->info("  Total: {$totalTaxRates}");
                $command->info("  With Subsidiary: {$taxRatesWithSubsidiary}");

                $command->info('Subsidiaries:');
                $command->info("  Total: {$totalSubsidiaries}");
                $command->info("  With Bill From: {$subsidiariesWithBillFrom}");
                $command->info("  With Custom Domain: {$subsidiariesWithCustomDomain}");
                $command->info("  With Tax Rate: {$subsidiariesWithTaxRate}");
                $command->info("  With Integration: {$subsidiariesWithIntegration}");

                $command->info('Companies:');
                $command->info("  Total: {$totalCompanies}");
                $command->info("  With Subsidiary: {$companiesWithSubsidiary}");
                $command->info("  With Tax Rate: {$companiesWithTaxRate}");

                $command->info('People:');
                $command->info("  Total: {$totalPeople}");
                $command->info("  With Manager: {$peopleWithManager}");
                $command->info("  With Company: {$peopleWithCompany}");
                $command->info("  With Subsidiary: {$peopleWithSubsidiary}");
                $command->info("  With Team: {$peopleWithTeam}");
                $command->info("  With APA: {$peopleWithApa}");
                

                $command->info('Workflows:');
                $command->info("  Total: {$totalWorkflows}");
                $command->info("  With Workflow: {$workflowWithWS}");

                $command->info('Projects:');
                $command->info("  Total: {$totalProjects}");
                $command->info("  With Company: {$projectsWithCompany}");
                $command->info("  With Project Manager: {$projectsWithProjectManager}");
                $command->info("  With Last Actor: {$projectsWithLastActor}");
                $command->info("  With Workflow: {$projectsWithWorkflow}");

                $command->info('Deals:');
                $command->info("  Total: {$totalDeals}");
                $command->info("  With Subsidiary: {$dealsWithSubsidiary}");
                $command->info("  With Creator: {$dealsWithCreator}");
                $command->info("  With Company: {$dealsWithCompany}");
                $command->info("  With Document Type: {$dealsWithDT}");
                $command->info("  With Responsible: {$dealsWithResponsible}");
                $command->info("  With Deal Status: {$dealsWithDealStatus}");
                $command->info("  With Project: {$dealsWithProject}");
                $command->info("  With Lost Reason: {$dealsWithLostReason}");
                $command->info("  With Contract: {$dealsWithContract}");
                $command->info("  With Contact: {$dealsWithContact}");
                $command->info("  With Subsidiary: {$dealsWithSubsidiary}");
                $command->info("  With Tax Rate: {$dealsWithTaxRate}");
                $command->info("  With APA: {$dealsWithApa}");

                $command->info('Document Types:');
                $command->info("  Total: {$totalDocumentTypes}");
                $command->info("  With Subsidiary: {$documentTypesWithSubsidiary}");
                $command->info("  With Document Style: {$documentTypesWithDS}");
                $command->info("  With Attachment: {$documentTypesWithAttachment}");

                $command->info('Contact Entries:');
                $command->info("  Total: {$totalContactEntries}");
                $command->info("  With Company: {$contactEntriesWithCompany}");
                $command->info("  With People: {$contactEntriesWithPeople}");
                $command->info("  With Invoice: {$contactEntriesWithInvoice}");
                $command->info("  With Subsidiary: {$contactEntriesWithSubsidiary}");
                $command->info("  With PO: {$contactWithPO}");

                // Validate relationships
                $this->validateRelationships($command);
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
    }
}
