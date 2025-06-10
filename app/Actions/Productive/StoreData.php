<?php

namespace App\Actions\Productive;

use App\Actions\Productive\Store\StoreCompany;
use App\Actions\Productive\Store\StoreContactEntry;
use App\Actions\Productive\Store\StoreContract;
use App\Actions\Productive\Store\StoreDeal;
use App\Actions\Productive\Store\StoreDealStatus;
use App\Actions\Productive\Store\StoreDocumentStyle;
use App\Actions\Productive\Store\StoreDocumentType;
use App\Actions\Productive\Store\StoreLostReason;
use App\Actions\Productive\Store\StorePeople;
use App\Actions\Productive\Store\StoreProject;
use App\Actions\Productive\Store\StoreSubsidiary;
use App\Actions\Productive\Store\StoreTaxRate;
use App\Actions\Productive\Store\StorePurchaseOrder;
use App\Actions\Productive\Store\StoreWorkflow;
use App\Actions\Productive\Store\StoreApprovalPolicyAssignment;
use App\Actions\Productive\Store\StoreApprovalPolicy;
use App\Actions\Productive\Store\StorePipeline;
use App\Actions\Productive\Store\StoreAttachment;
use App\Actions\Productive\Store\StoreBill;
use App\Actions\Productive\Store\StoreTeam;
use App\Actions\Productive\Store\StoreEmail;
use App\Actions\Productive\Store\StoreInvoice;
use App\Actions\Productive\Store\StoreInvoiceAttribution;
// use App\Actions\Productive\Store\StoreActivity;
use App\Actions\Productive\Store\StoreBoard;
use App\Actions\Productive\Store\StoreBooking;
use App\Actions\Productive\Store\StoreComment;
use App\Actions\Productive\Store\StoreDiscussion;
use App\Actions\Productive\Store\StoreEvent;
use App\Actions\Productive\Store\StoreExpense;
use App\Actions\Productive\Store\StoreIntegration;
use App\Actions\Productive\Store\StorePage;
use App\Actions\Productive\Store\StoreSection;
use App\Actions\Productive\Store\StoreTimeEntry;
use App\Actions\Productive\Store\StoreTimeEntryVersion;
use App\Actions\Productive\Store\StoreCustomField;
use App\Actions\Productive\Store\StoreCustomFieldOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StoreData extends AbstractAction
{
    protected array $requiredKeys = [
        'companies',
        'people',
        'projects',
        'tasks',
        'deals',
        'boards',
        'bookings',
        'comments',
        'discussions',
        'events',
        'expenses',
        'integrations',
        'pages',
        'sections'
    ];

    public function __construct(
        private StoreProject $storeProjectAction,
        private StoreCompany $storeCompanyAction,
        private StoreTimeEntry $storeTimeEntryAction,
        private StoreTimeEntryVersion $storeTimeEntryVersionAction,
        private StoreSubsidiary $storeSubsidiaryAction,
        private StoreTaxRate $storeTaxRateAction,
        private StoreDocumentStyle $storeDocumentStyleAction,
        private StoreDocumentType $storeDocumentTypeAction,
        private StoreWorkflow $storeWorkflowAction,
        private StoreDealStatus $storeDealStatusAction,
        private StoreLostReason $storeLostReasonAction,
        private StoreContactEntry $storeContactEntryAction,
        private StoreContract $storeContractAction,
        private StoreDeal $storeDealAction,
        private StorePeople $storePeopleAction,
        private StorePurchaseOrder $storePurchaseOrderAction,
        private StoreApprovalPolicyAssignment $storeApprovalPolicyAssignmentAction,
        private StoreApprovalPolicy $storeApprovalPolicyAction,
        private StorePipeline $storePipelineAction,
        private StoreAttachment $storeAttachmentAction,
        private StoreBill $storeBillAction,
        private StoreTeam $storeTeamAction,
        private StoreEmail $storeEmailAction,
        private StoreInvoice $storeInvoiceAction,
        private StoreInvoiceAttribution $storeInvoiceAttributionAction,
        private StoreBoard $storeBoardAction,
        private StoreBooking $storeBookingAction,
        private StoreComment $storeCommentAction,
        private StoreDiscussion $storeDiscussionAction,
        private StoreEvent $storeEventAction,
        private StoreExpense $storeExpenseAction,
        private StoreIntegration $storeIntegrationAction,
        private StorePage $storePageAction,
        private StoreSection $storeSectionAction,
        private StoreCustomField $storeCustomFieldAction,
        private StoreCustomFieldOption $storeCustomFieldOptionAction,
    ) {}

    /**
     * Store all fetched data in the database
     *
     * @param array $parameters
     * @return bool
     */
    public function handle(array $parameters = []): bool
    {
        $data = $parameters['data'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$data) {
            throw new \Exception('Data is required');
        }

        // Check if at least one of the required keys is present
        $hasRequiredData = false;
        foreach ($this->requiredKeys as $key) {
            if (!empty($data[$key])) {
                $hasRequiredData = true;
                break;
            }
        }

        if (!$hasRequiredData) {
            throw new \Exception('At least one of the following data types must be present: ' . implode(', ', $this->requiredKeys));
        }

        try {
            DB::beginTransaction();

            // First validate that we have data to store
            if (
                empty($data['companies']) &&
                empty($data['projects']) &&
                empty($data['document_styles']) &&
                empty($data['document_types']) &&
                empty($data['people']) &&
                empty($data['tax_rates']) &&
                empty($data['subsidiaries']) &&
                empty($data['workflows']) &&
                empty($data['contact_entries']) &&
                empty($data['lost_reasons']) &&
                empty($data['[pipeline]']) &&
                empty($data['deal_statuses']) &&
                empty($data['deal_statuses']) &&
                empty($data['approval_policies']) &&
                empty($data['approval_policy_assignments']) &&
                empty($data['contracts']) &&
                empty($data['deals']) &&
                empty($data['purchase_orders']) &&
                empty($data['emails']) &&
                empty($data['bills']) &&
                empty($data['attachments']) &&
                empty($data['teams']) &&
                empty($data['invoices']) &&
                empty($data['invoice_attributions']) &&
                empty($data['boards']) &&
                empty($data['bookings']) &&
                empty($data['comments']) &&
                empty($data['discussions']) &&
                empty($data['events']) &&
                empty($data['expenses']) &&
                empty($data['integrations']) &&
                empty($data['pages']) &&
                empty($data['sections'])
            ) {
                if ($command instanceof Command) {
                    $command->warn('No data fetched from Productive API. Skipping storage.');
                }
                return true;
            }

            // Store events
            if (!empty($data['events'])) {
                if ($command instanceof Command) {
                    $command->info('Storing events...');
                }

                $eventsSuccess = 0;
                $eventsError = 0;
                foreach ($data['events'] as $eventData) {
                    try {
                        $this->storeEventAction->handle([
                            'eventData' => $eventData,
                            'command' => $command
                        ]);
                        $eventsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store event (ID: {$eventData['id']}): " . $e->getMessage());
                        }
                        $eventsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Events: {$eventsSuccess} stored successfully, {$eventsError} failed");
                }
            }

            // Store subsidiaries
            if (!empty($data['subsidiaries'])) {
                if ($command instanceof Command) {
                    $command->info('Storing subsidiaries...');
                }

                $subsidiariesSuccess = 0;
                $subsidiariesError = 0;
                foreach ($data['subsidiaries'] as $subsidiaryData) {
                    try {
                        $this->storeSubsidiaryAction->handle([
                            'subsidiaryData' => $subsidiaryData,
                            'command' => $command
                        ]);
                        $subsidiariesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store subsidiary (ID: {$subsidiaryData['id']}): " . $e->getMessage());
                        }
                        $subsidiariesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Subsidiaries: {$subsidiariesSuccess} stored successfully, {$subsidiariesError} failed");
                }
            }

            // Store tax rates
            if (!empty($data['tax_rates'])) {
                if ($command instanceof Command) {
                    $command->info('Storing tax rates...');
                }

                $taxRatesSuccess = 0;
                $taxRatesError = 0;
                foreach ($data['tax_rates'] as $taxRateData) {
                    try {
                        $this->storeTaxRateAction->handle([
                            'taxRateData' => $taxRateData,
                            'command' => $command
                        ]);
                        $taxRatesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store tax rate (ID: {$taxRateData['id']}): " . $e->getMessage());
                        }
                        $taxRatesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Tax Rates: {$taxRatesSuccess} stored successfully, {$taxRatesError} failed");
                }
            }

            // Store document styles
            if (!empty($data['document_styles'])) {
                if ($command instanceof Command) {
                    $command->info('Storing document styles...');
                }

                $documentStylesSuccess = 0;
                $documentStylesError = 0;
                foreach ($data['document_styles'] as $documentStyleData) {
                    try {
                        $this->storeDocumentStyleAction->handle([
                            'documentStyleData' => $documentStyleData,
                            'command' => $command
                        ]);
                        $documentStylesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store document style (ID: {$documentStyleData['id']}): " . $e->getMessage());
                        }
                        $documentStylesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Document Styles: {$documentStylesSuccess} stored successfully, {$documentStylesError} failed");
                }
            }

            // Store pipelines
            if ($command instanceof Command) {
                $command->info("\nStoring pipelines...");
            }
            $successfulPipelines = 0;
            $failedPipelines = 0;
            foreach ($data['pipelines'] as $pipeline) {
                try {
                    $this->storePipelineAction->handle([
                        'pipelineData' => $pipeline,
                        'command' => $command
                    ]);
                    $successfulPipelines++;
                } catch (\Exception $e) {
                    $failedPipelines++;
                    if ($command instanceof Command) {
                        $command->error("Failed to store pipeline: " . $e->getMessage());
                    }
                }
            }
            if ($command instanceof Command) {
                $command->info("Successfully stored {$successfulPipelines} pipelines");
                if ($failedPipelines > 0) {
                    $command->warn("Failed to store {$failedPipelines} pipelines");
                }
            }

            // Store deal statuses
            if (!empty($data['deal_statuses'])) {
                if ($command instanceof Command) {
                    $command->info('Storing deal statuses...');
                }

                $dealStatusesSuccess = 0;
                $dealStatusesError = 0;
                foreach ($data['deal_statuses'] as $dealStatusData) {
                    try {
                        $this->storeDealStatusAction->handle([
                            'dealStatusData' => $dealStatusData,
                            'command' => $command
                        ]);
                        $dealStatusesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store deal status (ID: {$dealStatusData['id']}): " . $e->getMessage());
                        }
                        $dealStatusesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Deal Statuses: {$dealStatusesSuccess} stored successfully, {$dealStatusesError} failed");
                }
            }

            // Store lost reasons
            if (!empty($data['lost_reasons'])) {
                if ($command instanceof Command) {
                    $command->info('Storing lost reasons...');
                }

                $lostReasonsSuccess = 0;
                $lostReasonsError = 0;
                foreach ($data['lost_reasons'] as $lostReasonData) {
                    try {
                        $this->storeLostReasonAction->handle([
                            'lostReasonData' => $lostReasonData,
                            'command' => $command
                        ]);
                        $lostReasonsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store lost reason (ID: {$lostReasonData['id']}): " . $e->getMessage());
                        }
                        $lostReasonsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Lost Reasons: {$lostReasonsSuccess} stored successfully, {$lostReasonsError} failed");
                }
            }

            // Store companies
            if (!empty($data['companies'])) {
                if ($command instanceof Command) {
                    $command->info('Storing companies...');
                }

                $companiesSuccess = 0;
                $companiesError = 0;
                foreach ($data['companies'] as $companyData) {
                    try {
                        $this->storeCompanyAction->handle([
                            'companyData' => $companyData,
                            'command' => $command
                        ]);
                        $companiesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store company (ID: {$companyData['id']}): " . $e->getMessage());
                        }
                        $companiesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Companies: {$companiesSuccess} stored successfully, {$companiesError} failed");
                }
            }

            // Store people
            if (!empty($data['people'])) {
                if ($command instanceof Command) {
                    $command->info('Storing people...');
                }

                $peopleSuccess = 0;
                $peopleError = 0;
                foreach ($data['people'] as $personData) {
                    try {
                        $this->storePeopleAction->handle([
                            'personData' => $personData,
                            'command' => $command
                        ]);
                        $peopleSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store person (ID: {$personData['id']}): " . $e->getMessage());
                        }
                        $peopleError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("People: {$peopleSuccess} stored successfully, {$peopleError} failed");
                }
            }

            // Store workflows
            if (!empty($data['workflows'])) {
                if ($command instanceof Command) {
                    $command->info('Storing workflows...');
                }

                $workflowsSuccess = 0;
                $workflowsError = 0;
                foreach ($data['workflows'] as $workflowData) {
                    try {
                        $this->storeWorkflowAction->handle([
                            'workflowData' => $workflowData,
                            'command' => $command
                        ]);
                        $workflowsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store workflow (ID: {$workflowData['id']}): " . $e->getMessage());
                        }
                        $workflowsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Workflows: {$workflowsSuccess} stored successfully, {$workflowsError} failed");
                }
            }

            // Store projects
            if (!empty($data['projects'])) {
                if ($command instanceof Command) {
                    $command->info('Storing projects...');
                }

                $projectsSuccess = 0;
                $projectsError = 0;
                foreach ($data['projects'] as $projectData) {
                    try {
                        $this->storeProjectAction->handle([
                            'projectData' => $projectData,
                            'command' => $command
                        ]);
                        $projectsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store project (ID: {$projectData['id']}): " . $e->getMessage());
                        }
                        $projectsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Projects: {$projectsSuccess} stored successfully, {$projectsError} failed");
                }
            }

            // Store boards
            if (!empty($data['boards'])) {
                if ($command instanceof Command) {
                    $command->info('Storing boards...');
                }

                $boardsSuccess = 0;
                $boardsError = 0;
                foreach ($data['boards'] as $boardData) {
                    try {
                        $this->storeBoardAction->handle([
                            'boardData' => $boardData,
                            'command' => $command
                        ]);
                        $boardsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store board (ID: {$boardData['id']}): " . $e->getMessage());
                        }
                        $boardsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Boards: {$boardsSuccess} stored successfully, {$boardsError} failed");
                }
            }

            // Store document types
            if (!empty($data['document_types'])) {
                if ($command instanceof Command) {
                    $command->info('Storing document types...');
                }

                $documentTypesSuccess = 0;
                $documentTypesError = 0;
                foreach ($data['document_types'] as $documentTypeData) {
                    try {
                        $this->storeDocumentTypeAction->handle([
                            'documentTypeData' => $documentTypeData,
                            'command' => $command
                        ]);
                        $documentTypesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store document type (ID: {$documentTypeData['id']}): " . $e->getMessage());
                        }
                        $documentTypesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Document Types: {$documentTypesSuccess} stored successfully, {$documentTypesError} failed");
                }
            }

            // Store contact entries
            if (!empty($data['contact_entries'])) {
                if ($command instanceof Command) {
                    $command->info('Storing contact entries...');
                }

                $contactEntriesSuccess = 0;
                $contactEntriesError = 0;
                foreach ($data['contact_entries'] as $contactEntryData) {
                    try {
                        $this->storeContactEntryAction->handle([
                            'contactEntryData' => $contactEntryData,
                            'command' => $command
                        ]);
                        $contactEntriesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store contact entry (ID: {$contactEntryData['id']}): " . $e->getMessage());
                        }
                        $contactEntriesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Contact Entries: {$contactEntriesSuccess} stored successfully, {$contactEntriesError} failed");
                }
            }

            // Store contracts
            if (!empty($data['contracts'])) {
                if ($command instanceof Command) {
                    $command->info('Storing contracts...');
                }

                $contractsSuccess = 0;
                $contractsError = 0;
                foreach ($data['contracts'] as $contractData) {
                    try {
                        $this->storeContractAction->handle([
                            'contractData' => $contractData,
                            'command' => $command
                        ]);
                        $contractsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store contract (ID: {$contractData['id']}): " . $e->getMessage());
                        }
                        $contractsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Contracts: {$contractsSuccess} stored successfully, {$contractsError} failed");
                }
            }

            // Store approval policy assignments
            if (!empty($data['approval_policy_assignments'])) {
                if ($command instanceof Command) {
                    $command->info('Storing approval policy assignments...');
                }

                $assignmentsSuccess = 0;
                $assignmentsError = 0;
                foreach ($data['approval_policy_assignments'] as $assignmentData) {
                    try {
                        $this->storeApprovalPolicyAssignmentAction->handle([
                            'assignmentData' => $assignmentData,
                            'command' => $command
                        ]);
                        $assignmentsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store approval policy assignment (ID: {$assignmentData['id']}): " . $e->getMessage());
                        }
                        $assignmentsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Approval Policy Assignments: {$assignmentsSuccess} stored successfully, {$assignmentsError} failed");
                }
            }

            // Store deals
            if (!empty($data['deals'])) {
                if ($command instanceof Command) {
                    $command->info('Storing deals...');
                }

                $dealsSuccess = 0;
                $dealsError = 0;
                foreach ($data['deals'] as $dealData) {
                    try {
                        $this->storeDealAction->handle([
                            'dealData' => $dealData,
                            'command' => $command
                        ]);
                        $dealsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store deal (ID: {$dealData['id']}): " . $e->getMessage());
                        }
                        $dealsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Deals: {$dealsSuccess} stored successfully, {$dealsError} failed");
                }
            }

            // Store purchase orders
            if (!empty($data['purchase_orders'])) {
                if ($command instanceof Command) {
                    $command->info('Storing purchase orders...');
                }

                $purchaseOrdersSuccess = 0;
                $purchaseOrdersError = 0;
                foreach ($data['purchase_orders'] as $purchaseOrderData) {
                    try {
                        $this->storePurchaseOrderAction->handle([
                            'purchaseOrderData' => $purchaseOrderData,
                            'command' => $command
                        ]);
                        $purchaseOrdersSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store purchase order (ID: {$purchaseOrderData['id']}): " . $e->getMessage());
                        }
                        $purchaseOrdersError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Purchase Orders: {$purchaseOrdersSuccess} stored successfully, {$purchaseOrdersError} failed");
                }
            }

            // Store emails
            if (!empty($data['emails'])) {
                if ($command instanceof Command) {
                    $command->info('Storing emails...');
                }

                $emailsSuccess = 0;
                $emailsError = 0;
                foreach ($data['emails'] as $emailData) {
                    try {
                        $this->storeEmailAction->handle([
                            'emailData' => $emailData,
                            'command' => $command
                        ]);
                        $emailsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store email (ID: {$emailData['id']}): " . $e->getMessage());
                        }
                        $emailsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Emails: {$emailsSuccess} stored successfully, {$emailsError} failed");
                }
            }

            // Store bills
            if (!empty($data['bills'])) {
                if ($command instanceof Command) {
                    $command->info('Storing bills...');
                }

                $billsSuccess = 0;
                $billsError = 0;
                foreach ($data['bills'] as $billData) {
                    try {
                        $this->storeBillAction->handle([
                            'billData' => $billData,
                            'command' => $command
                        ]);
                        $billsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store bill (ID: {$billData['id']}): " . $e->getMessage());
                        }
                        $billsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Bills: {$billsSuccess} stored successfully, {$billsError} failed");
                }
            }

            // Store attachments
            if (!empty($data['attachments'])) {
                if ($command instanceof Command) {
                    $command->info('Storing attachments...');
                }

                $attachmentsSuccess = 0;
                $attachmentsError = 0;
                foreach ($data['attachments'] as $attachmentData) {
                    try {
                        $this->storeAttachmentAction->handle([
                            'attachmentData' => $attachmentData,
                            'command' => $command
                        ]);
                        $attachmentsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store attachment (ID: {$attachmentData['id']}): " . $e->getMessage());
                        }
                        $attachmentsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Attachments: {$attachmentsSuccess} stored successfully, {$attachmentsError} failed");
                }
            }

            // Store bookings
            if (!empty($data['bookings'])) {
                if ($command instanceof Command) {
                    $command->info('Storing bookings...');
                }

                $bookingsSuccess = 0;
                $bookingsError = 0;
                foreach ($data['bookings'] as $bookingData) {
                    try {
                        $this->storeBookingAction->handle([
                            'bookingData' => $bookingData,
                            'command' => $command
                        ]);
                        $bookingsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store booking (ID: {$bookingData['id']}): " . $e->getMessage());
                        }
                        $bookingsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Bookings: {$bookingsSuccess} stored successfully, {$bookingsError} failed");
                }
            }

            // Store teams
            if (!empty($data['teams'])) {
                if ($command instanceof Command) {
                    $command->info('Storing teams...');
                }

                $teamsSuccess = 0;
                $teamsError = 0;
                foreach ($data['teams'] as $teamData) {
                    try {
                        $this->storeTeamAction->handle([
                            'teamData' => $teamData,
                            'command' => $command
                        ]);
                        $teamsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store team (ID: {$teamData['id']}): " . $e->getMessage());
                        }
                        $teamsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Teams: {$teamsSuccess} stored successfully, {$teamsError} failed");
                }
            }

            // Store invoices
            if (!empty($data['invoices'])) {
                if ($command instanceof Command) {
                    $command->info('Storing invoices...');
                }

                $invoicesSuccess = 0;
                $invoicesError = 0;
                foreach ($data['invoices'] as $invoiceData) {
                    try {
                        $this->storeInvoiceAction->handle([
                            'invoiceData' => $invoiceData,
                            'command' => $command
                        ]);
                        $invoicesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store invoice (ID: {$invoiceData['id']}): " . $e->getMessage());
                        }
                        $invoicesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Invoices: {$invoicesSuccess} stored successfully, {$invoicesError} failed");
                }
            }

            // Store invoice attributions
            if (!empty($data['invoice_attributions'])) {
                if ($command instanceof Command) {
                    $command->info('Storing invoice attributions...');
                }

                $invoiceAttributionsSuccess = 0;
                $invoiceAttributionsError = 0;
                foreach ($data['invoice_attributions'] as $invoiceAttributionData) {
                    try {
                        $this->storeInvoiceAttributionAction->handle([
                            'invoiceAttributionData' => $invoiceAttributionData,
                            'command' => $command
                        ]);
                        $invoiceAttributionsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store invoice attribution (ID: {$invoiceAttributionData['id']}): " . $e->getMessage());
                        }
                        $invoiceAttributionsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Invoice Attributions: {$invoiceAttributionsSuccess} stored successfully, {$invoiceAttributionsError} failed");
                }
            }

            // Store comments
            if (!empty($data['comments'])) {
                if ($command instanceof Command) {
                    $command->info('Storing comments...');
                }

                $commentsSuccess = 0;
                $commentsError = 0;
                foreach ($data['comments'] as $commentData) {
                    try {
                        $this->storeCommentAction->handle([
                            'commentData' => $commentData,
                            'command' => $command
                        ]);
                        $commentsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store comment (ID: {$commentData['id']}): " . $e->getMessage());
                        }
                        $commentsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Comments: {$commentsSuccess} stored successfully, {$commentsError} failed");
                }
            }

            // Store discussions
            if (!empty($data['discussions'])) {
                if ($command instanceof Command) {
                    $command->info('Storing discussions...');
                }

                $discussionsSuccess = 0;
                $discussionsError = 0;
                foreach ($data['discussions'] as $discussionData) {
                    try {
                        $this->storeDiscussionAction->handle([
                            'discussionData' => $discussionData,
                            'command' => $command
                        ]);
                        $discussionsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store discussion (ID: {$discussionData['id']}): " . $e->getMessage());
                        }
                        $discussionsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Discussions: {$discussionsSuccess} stored successfully, {$discussionsError} failed");
                }
            }

            // Store expenses
            if (!empty($data['expenses'])) {
                if ($command instanceof Command) {
                    $command->info('Storing expenses...');
                }

                $expensesSuccess = 0;
                $expensesError = 0;
                foreach ($data['expenses'] as $expenseData) {
                    try {
                        $this->storeExpenseAction->handle([
                            'expenseData' => $expenseData,
                            'command' => $command
                        ]);
                        $expensesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store expense (ID: {$expenseData['id']}): " . $e->getMessage());
                        }
                        $expensesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Expenses: {$expensesSuccess} stored successfully, {$expensesError} failed");
                }
            }

            // Store integrations
            if (!empty($data['integrations'])) {
                if ($command instanceof Command) {
                    $command->info('Storing integrations...');
                }

                $integrationsSuccess = 0;
                $integrationsError = 0;
                foreach ($data['integrations'] as $integrationData) {
                    try {
                        $this->storeIntegrationAction->handle([
                            'integrationData' => $integrationData,
                            'command' => $command
                        ]);
                        $integrationsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store integration (ID: {$integrationData['id']}): " . $e->getMessage());
                        }
                        $integrationsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Integrations: {$integrationsSuccess} stored successfully, {$integrationsError} failed");
                }
            }

            // Store pages
            if (!empty($data['pages'])) {
                if ($command instanceof Command) {
                    $command->info('Storing pages...');
                }

                $pagesSuccess = 0;
                $pagesError = 0;
                foreach ($data['pages'] as $pageData) {
                    try {
                        $this->storePageAction->handle([
                            'pageData' => $pageData,
                            'command' => $command
                        ]);
                        $pagesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store page (ID: {$pageData['id']}): " . $e->getMessage());
                        }
                        $pagesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Pages: {$pagesSuccess} stored successfully, {$pagesError} failed");
                }
            }

            // Store sections
            if (!empty($data['sections'])) {
                if ($command instanceof Command) {
                    $command->info('Storing sections...');
                }

                $sectionsSuccess = 0;
                $sectionsError = 0;
                foreach ($data['sections'] as $sectionData) {
                    try {
                        $this->storeSectionAction->handle([
                            'sectionData' => $sectionData,
                            'command' => $command
                        ]);
                        $sectionsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store section (ID: {$sectionData['id']}): " . $e->getMessage());
                        }
                        $sectionsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Sections: {$sectionsSuccess} stored successfully, {$sectionsError} failed");
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($command instanceof Command) {
                $command->error('Error storing data: ' . $e->getMessage());
            }
            return false;
        }
    }
}
