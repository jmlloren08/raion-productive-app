<?php

namespace App\Console\Commands;

use App\Actions\Productive\InitializeClient;
use App\Actions\Productive\Fetch\FetchCompanies;
use App\Actions\Productive\Fetch\FetchContactEntries;
use App\Actions\Productive\Fetch\FetchContracts;
use App\Actions\Productive\Fetch\FetchDeals;
use App\Actions\Productive\Fetch\FetchDealStatus;
use App\Actions\Productive\Fetch\FetchDocumentStyles;
use App\Actions\Productive\Fetch\FetchDocumentTypes;
use App\Actions\Productive\Fetch\FetchLostReasons;
use App\Actions\Productive\Fetch\FetchPeople;
use App\Actions\Productive\Fetch\FetchProjects;
use App\Actions\Productive\Fetch\FetchSubsidiaries;
use App\Actions\Productive\Fetch\FetchTaxRates;
use App\Actions\Productive\Fetch\FetchWorkflows;
use App\Actions\Productive\Fetch\FetchPurchaseOrders;
use App\Actions\Productive\Fetch\FetchApprovalPolicyAssignments;
use App\Actions\Productive\Fetch\FetchApprovalPolicies;
use App\Actions\Productive\Fetch\FetchPipelines;
use App\Actions\Productive\Fetch\FetchAttachments;
use App\Actions\Productive\Fetch\FetchBills;
use App\Actions\Productive\Fetch\FetchTeams;
use App\Actions\Productive\Fetch\FetchEmails;
use App\Actions\Productive\Fetch\FetchInvoices;
use App\Actions\Productive\Fetch\FetchInvoiceAttributions;
use App\Actions\Productive\Fetch\FetchSections;
// use App\Actions\Productive\Fetch\FetchActivities;
use App\Actions\Productive\Fetch\FetchBoards;
use App\Actions\Productive\Fetch\FetchBookings;
use App\Actions\Productive\Fetch\FetchComments;
use App\Actions\Productive\Fetch\FetchDiscussions;
use App\Actions\Productive\Fetch\FetchEvents;
use App\Actions\Productive\Fetch\FetchExpenses;
use App\Actions\Productive\Fetch\FetchIntegrations;
use App\Actions\Productive\Fetch\FetchPages;
use App\Actions\Productive\FetchTimeEntries;
use App\Actions\Productive\FetchTimeEntryVersions;
use App\Actions\Productive\StoreData;
use App\Actions\Productive\ValidateDataIntegrity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProductiveDataRefactored extends Command
{
    protected $signature = 'sync:productive';
    protected $description = 'Sync data from Productive.io API using refactored action classes';
    private $data = [
        'companies' => [],
        'projects' => [],
        'time_entries' => [],
        'time_entry_versions' => [],
        'people' => [],
        'workflows' => [],
        'deals' => [],
        'document_types' => [],
        'contact_entries' => [],
        'subsidiaries' => [],
        'tax_rates' => [],
        'document_styles' => [],
        'deal_statuses' => [],
        'lost_reasons' => [],
        'contracts' => [],
        'purchase_orders' => [],
        'approval_policy_assignments' => [],
        'approval_policies' => [],
        'pipelines' => [],
        'attachments' => [],
        'bills' => [],
        'teams' => [],
        'emails' => [],
        'invoices' => [],
        'invoice_attributions' => [],
        'boards' => [],
        'bookings' => [],
        'comments' => [],
        'discussions' => [],
        'events' => [],
        'expenses' => [],
        'integrations' => [],
        'pages' => [],
        'sections' => [],
        // 'activities' => []
    ];

    public function __construct(
        private InitializeClient $initializeClientAction,
        private FetchCompanies $fetchCompaniesAction,
        private FetchProjects $fetchProjectsAction,
        private FetchTimeEntries $fetchTimeEntriesAction,
        private FetchTimeEntryVersions $fetchTimeEntryVersionsAction,
        private FetchPeople $fetchPeopleAction,
        private FetchWorkflows $fetchWorkflowsAction,
        private FetchDeals $fetchDealsAction,
        private FetchDocumentTypes $fetchDocumentTypesAction,
        private FetchContactEntries $fetchContactEntriesAction,
        private FetchSubsidiaries $fetchSubsidiariesAction,
        private FetchTaxRates $fetchTaxRatesAction,
        private FetchDocumentStyles $fetchDocumentStylesAction,
        private FetchDealStatus $fetchDealStatusAction,
        private FetchLostReasons $fetchLostReasonsAction,
        private FetchContracts $fetchContractsAction,
        private FetchPurchaseOrders $fetchPurchaseOrdersAction,
        private FetchApprovalPolicyAssignments $fetchApprovalPolicyAssignmentsAction,
        private FetchApprovalPolicies $fetchApprovalPoliciesAction,
        private FetchPipelines $fetchPipelinesAction,
        private FetchAttachments $fetchAttachmentsAction,
        private FetchBills $fetchBillsAction,
        private FetchTeams $fetchTeamsAction,
        private FetchEmails $fetchEmailsAction,
        private FetchInvoices $fetchInvoicesAction,
        private FetchInvoiceAttributions $fetchInvoiceAttributionsAction,
        private FetchBoards $fetchBoardsAction,
        private FetchBookings $fetchBookingsAction,
        private FetchComments $fetchCommentsAction,
        private FetchDiscussions $fetchDiscussionsAction,
        private FetchEvents $fetchEventsAction,
        private FetchExpenses $fetchExpensesAction,
        private FetchIntegrations $fetchIntegrationAction,
        private FetchPages $fetchPagesAction,
        private FetchSections $fetchSectionsAction,
        private StoreData $storeDataAction,
        private ValidateDataIntegrity $validateDataIntegrityAction
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting Productive.io data sync...');
        $startTime = microtime(true);

        try {
            // Debug: Output configuration values
            $this->info('API URL: ' . config('services.productive.api_url'));
            $this->info('API Token: ' . (config('services.productive.api_token') ? 'Set' : 'Not set'));
            $this->info('Organization ID: ' . config('services.productive.organization_id'));

            // Initialize API client
            $apiClient = $this->initializeClientAction->handle();
            $this->info('Fetching data from Productive API...');

            // Fetch events
            $events = $this->fetchEventsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$events['success']) {
                $this->error('Failed to fetch events: ' . ($events['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['events'] = $events['events'];
            
            // Fetch subsidiaries
            $subsidiaries = $this->fetchSubsidiariesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$subsidiaries['success']) {
                $this->error('Failed to fetch subsidiaries: ' . ($subsidiaries['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['subsidiaries'] = $subsidiaries['subsidiaries'];

            // Fetch tax rates
            $taxRates = $this->fetchTaxRatesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$taxRates['success']) {
                $this->error('Failed to fetch tax rates: ' . ($taxRates['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['tax_rates'] = $taxRates['tax_rates'];

            // Fetch document styles
            $documentStyles = $this->fetchDocumentStylesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$documentStyles['success']) {
                $this->error('Failed to fetch document styles: ' . ($documentStyles['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['document_styles'] = $documentStyles['document_styles'];

            // Fetch deal statuses
            $dealStatuses = $this->fetchDealStatusAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$dealStatuses['success']) {
                $this->error('Failed to fetch deal statuses: ' . ($dealStatuses['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['deal_statuses'] = $dealStatuses['deal_statuses'];

            // Fetch lost reasons
            $lostReasons = $this->fetchLostReasonsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$lostReasons['success']) {
                $this->error('Failed to fetch lost reasons: ' . ($lostReasons['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['lost_reasons'] = $lostReasons['lost_reasons'];

            // Fetch companies
            $companies = $this->fetchCompaniesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$companies['success']) {
                $this->error('Failed to fetch companies: ' . ($companies['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['companies'] = $companies['companies'];

            // Fetch people
            $people = $this->fetchPeopleAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$people['success']) {
                $this->error('Failed to fetch people: ' . ($people['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['people'] = $people['people'];

            // Fetch workflows
            $workflows = $this->fetchWorkflowsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$workflows['success']) {
                $this->error('Failed to fetch workflows: ' . ($workflows['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['workflows'] = $workflows['workflows'];

            // Fetch boards
            $boards = $this->fetchBoardsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$boards['success']) {
                $this->error('Failed to fetch boards: ' . ($boards['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['boards'] = $boards['boards'];

            // Fetch projects
            $projects = $this->fetchProjectsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$projects['success']) {
                $this->error('Failed to fetch projects: ' . ($projects['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['projects'] = $projects['projects'];

            // Fetch document types
            $documentTypes = $this->fetchDocumentTypesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$documentTypes['success']) {
                $this->error('Failed to fetch document types: ' . ($documentTypes['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['document_types'] = $documentTypes['document_types'];

            // Fetch contact entries
            $contactEntries = $this->fetchContactEntriesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$contactEntries['success']) {
                $this->error('Failed to fetch contact entries: ' . ($contactEntries['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['contact_entries'] = $contactEntries['contact_entries'];

            // Fetch contracts
            $contracts = $this->fetchContractsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$contracts['success']) {
                $this->error('Failed to fetch contracts: ' . ($contracts['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['contracts'] = $contracts['contracts'];

            // Fetch approval policy assignments
            $approvalPolicyAssignments = $this->fetchApprovalPolicyAssignmentsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$approvalPolicyAssignments['success']) {
                $this->error('Failed to fetch approval policy assignments: ' . ($approvalPolicyAssignments['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['approval_policy_assignments'] = $approvalPolicyAssignments['approval_policy_assignments'];

            // Fetch approval policies
            $approvalPolicies = $this->fetchApprovalPoliciesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$approvalPolicies['success']) {
                $this->error('Failed to fetch approval policies: ' . ($approvalPolicies['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['approval_policies'] = $approvalPolicies['approval_policies'];

            // Fetch pipelines
            $pipelines = $this->fetchPipelinesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$pipelines['success']) {
                $this->error('Failed to fetch pipelines: ' . ($pipelines['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['pipelines'] = $pipelines['pipelines'];

            // Fetch deals
            $deals = $this->fetchDealsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$deals['success']) {
                $this->error('Failed to fetch deals: ' . ($deals['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['deals'] = $deals['deals'];

            // Fetch purchase orders
            $purchaseOrders = $this->fetchPurchaseOrdersAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$purchaseOrders['success']) {
                $this->error('Failed to fetch purchase orders: ' . ($purchaseOrders['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['purchase_orders'] = $purchaseOrders['purchase_orders'];

            // Fetch emails
            $emails = $this->fetchEmailsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$emails['success']) {
                $this->error('Failed to fetch emails: ' . ($emails['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['emails'] = $emails['emails'];

            // Fetch bills
            $bills = $this->fetchBillsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$bills['success']) {
                $this->error('Failed to fetch bills: ' . ($bills['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['bills'] = $bills['bills'];

            // Fetch attachments
            $attachments = $this->fetchAttachmentsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$attachments['success']) {
                $this->error('Failed to fetch attachments: ' . ($attachments['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['attachments'] = $attachments['attachments'];

            // Fetch bookings
            $bookings = $this->fetchBookingsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$bookings['success']) {
                $this->error('Failed to fetch bookings: ' . ($bookings['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['bookings'] = $bookings['bookings'];

            // Fetch teams
            $teams = $this->fetchTeamsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$teams['success']) {
                $this->error('Failed to fetch teams: ' . ($teams['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['teams'] = $teams['teams'];

            // Fetch invoices
            $invoices = $this->fetchInvoicesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$invoices['success']) {
                $this->error('Failed to fetch invoices: ' . ($invoices['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['invoices'] = $invoices['invoices'];

            // Fetch invoice attributions
            $invoiceAttributions = $this->fetchInvoiceAttributionsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$invoiceAttributions['success']) {
                $this->error('Failed to fetch invoice attributions: ' . ($invoiceAttributions['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['invoice_attributions'] = $invoiceAttributions['invoice_attributions'];

            // Fetch comments
            $comments = $this->fetchCommentsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$comments['success']) {
                $this->error('Failed to fetch comments: ' . ($comments['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['comments'] = $comments['comments'];

            // Fetch discussions
            $discussions = $this->fetchDiscussionsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$discussions['success']) {
                $this->error('Failed to fetch discussions: ' . ($discussions['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['discussions'] = $discussions['discussions'];

            // Fetch expenses
            $expenses = $this->fetchExpensesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$expenses['success']) {
                $this->error('Failed to fetch expenses: ' . ($expenses['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['expenses'] = $expenses['expenses'];

            // Fetch integrations
            $integrations = $this->fetchIntegrationAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$integrations['success']) {
                $this->error('Failed to fetch integrations: ' . ($integrations['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['integrations'] = $integrations['integrations'];

            // Fetch pages
            $pages = $this->fetchPagesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$pages['success']) {
                $this->error('Failed to fetch pages: ' . ($pages['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['pages'] = $pages['pages'];

            // Fetch sections
            $sections = $this->fetchSectionsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$sections['success']) {
                $this->error('Failed to fetch sections: ' . ($sections['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['sections'] = $sections['sections'];

            // Fetch time entries
            $timeEntries = $this->fetchTimeEntriesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$timeEntries['success']) {
                $this->error('Failed to fetch time entries: ' . ($timeEntries['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['time_entries'] = $timeEntries['time_entries'];

            // Fetch time entry versions
            $timeEntryVersions = $this->fetchTimeEntryVersionsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$timeEntryVersions['success']) {
                $this->error('Failed to fetch time entry versions: ' . ($timeEntryVersions['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['time_entry_versions'] = $timeEntryVersions['time_entry_versions'];

            // Store data in MySQL
            $this->info('Storing data in database...');
            $storageSuccess = $this->storeDataAction->handle([
                'data' => $this->data,
                'command' => $this
            ]);

            if (!$storageSuccess) {
                $this->error('Failed to store data in database. Aborting sync process.');
                return 1;
            }

            // Validate data integrity
            $this->info('Validating data integrity...');
            $this->validateDataIntegrityAction->handle([
                'command' => $this
            ]);

            // Report statistics
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            $this->info('==== Sync Summary ====');
            $this->info('Subsidiaries synced: ' . count($this->data['subsidiaries']));
            $this->info('Tax Rates synced: ' . count($this->data['tax_rates']));
            $this->info('Document Styles synced: ' . count($this->data['document_styles']));
            $this->info('Lost Reasons synced: ' . count($this->data['lost_reasons']));
            $this->info('Companies synced: ' . count($this->data['companies']));
            $this->info('People synced: ' . count($this->data['people']));
            $this->info('Workflows synced: ' . count($this->data['workflows']));
            $this->info('Deals synced: ' . count($this->data['deals']));
            $this->info('Document Types synced: ' . count($this->data['document_types']));
            $this->info('Contact Entries synced: ' . count($this->data['contact_entries']));
            $this->info('Projects synced: ' . count($this->data['projects']));
            $this->info('Contracts synced: ' . count($this->data['contracts']));
            $this->info('Approval Policy Assignments synced: ' . count($this->data['approval_policy_assignments']));
            $this->info('Deal Statuses synced: ' . count($this->data['deal_statuses']));
            $this->info('Purchase Orders synced: ' . count($this->data['purchase_orders']));
            $this->info('Pipelines synced: ' . count($this->data['pipelines']));
            $this->info('Attachments synced: ' . count($this->data['attachments']));
            $this->info('Bills synced: ' . count($this->data['bills']));
            $this->info('Teams synced: ' . count($this->data['teams']));
            $this->info('Emails synced: ' . count($this->data['emails']));
            $this->info('Invoices synced: ' . count($this->data['invoices']));
            $this->info('Invoice Attributions synced: ' . count($this->data['invoice_attributions']));
            $this->info('Boards synced: ' . count($this->data['boards']));
            $this->info('Bookings synced: ' . count($this->data['bookings']));
            $this->info('Comments synced: ' . count($this->data['comments']));
            $this->info('Events synced: ' . count($this->data['events']));
            $this->info('Discussions synced: ' . count($this->data['discussions']));
            $this->info('Expenses synced: ' . count($this->data['expenses']));
            $this->info('Integrations synced: ' . count($this->data['integrations']));
            $this->info('Pages synced: ' . count($this->data['pages']));
            $this->info('Sections synced: ' . count($this->data['sections']));
            $this->info('Time Entries synced: ' . count($this->data['time_entries']));
            $this->info('Time Entry Versions synced: ' . count($this->data['time_entry_versions']));
            $this->info('Execution time: ' . $executionTime . ' seconds');
            $this->info('Sync completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Productive sync error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
