<?php

namespace App\Console\Commands;

use App\Actions\Productive\InitializeClient;
use App\Actions\Productive\FetchCompanies;
use App\Actions\Productive\FetchProjects;
use App\Actions\Productive\FetchPeople;
use App\Actions\Productive\FetchWorkflows;
use App\Actions\Productive\FetchDeals;
use App\Actions\Productive\StoreData;
use App\Actions\Productive\ValidateDataIntegrity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncProductiveDataRefactored extends Command
{
    protected $signature = 'sync:productive';
    protected $description = 'Sync data from Productive.io API using refactored action classes';
    private $data = [
        'companies' => [],
        'projects' => [],
        'people' => [],
        'workflows' => [],
        'deals' => []
    ];

    public function __construct(
        private InitializeClient $initializeClientAction,
        private FetchCompanies $fetchCompaniesAction,
        private FetchProjects $fetchProjectsAction,
        private FetchPeople $fetchPeopleAction,
        private FetchWorkflows $fetchWorkflowsAction,
        private FetchDeals $fetchDealsAction,
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
            $this->info('Companies synced: ' . count($this->data['companies']));
            $this->info('People synced: ' . count($this->data['people']));
            $this->info('Workflows synced: ' . count($this->data['workflows']));
            $this->info('Deals synced: ' . count($this->data['deals']));
            $this->info('Projects synced: ' . count($this->data['projects']));
            $this->info('Execution time: ' . $executionTime . ' seconds');
            $this->info('Sync completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
