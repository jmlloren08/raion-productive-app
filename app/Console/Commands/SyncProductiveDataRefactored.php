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
use App\Actions\Productive\StoreData;
use App\Actions\Productive\ValidateDataIntegrity;
use App\Models\ProductiveContactEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncProductiveDataRefactored extends Command
{
    protected $signature = 'sync:productive';
    protected $description = 'Sync data from Productive.io API using refactored action classes';
    private $data = [
        'companies' => [],
        'projects' => [],
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
        'contracts' => []
    ];

    public function __construct(
        private InitializeClient $initializeClientAction,
        private FetchCompanies $fetchCompaniesAction,
        private FetchProjects $fetchProjectsAction,
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

            // Fetch subsidiaries first since other entities might depend on them
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
            $integrityStats = $this->validateDataIntegrityAction->handle([
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
            $this->info('Deal Statuses synced: ' . count($this->data['deal_statuses']));
            $this->info('Contracts synced: ' . count($this->data['contracts']));
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
