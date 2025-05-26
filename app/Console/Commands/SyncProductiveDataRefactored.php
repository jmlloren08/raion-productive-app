<?php

namespace App\Console\Commands;

use App\Actions\Productive\InitializeClient;
use App\Actions\Productive\FetchCompanies;
use App\Actions\Productive\FetchProjects;
use App\Actions\Productive\FetchDeals;
use App\Actions\Productive\FetchTimeEntries;
use App\Actions\Productive\FetchTimeEntryVersions;
use App\Actions\Productive\StoreData;
use App\Actions\Productive\ValidateDataIntegrity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncProductiveDataRefactored extends Command
{
    protected $signature = 'sync:productive-refactored';
    protected $description = 'Sync data from Productive.io API using refactored action classes';
    private $data = [
        'companies' => [],
        'projects' => [],
        'deals' => [],
        'time_entries' => [],
        'time_entry_versions' => []
    ];

    public function __construct(
        private InitializeClient $initializeClientAction,
        private FetchCompanies $fetchCompaniesAction,
        private FetchProjects $fetchProjectsAction,
        private FetchDeals $fetchDealsAction,
        private FetchTimeEntries $fetchTimeEntriesAction,
        private FetchTimeEntryVersions $fetchTimeEntryVersionsAction,
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
            $this->info('Organization ID: ' . config('services.productive.organization_id'));            // 1. Initialize API client and fetch all data from Productive API
            $client = $this->initializeClientAction->handle();
            $this->info('Fetching data from Productive API...');
            $companyFetchResult = $this->fetchCompaniesAction->handle([
                'client' => $client,
                'command' => $this
            ]);

            if (!$companyFetchResult['success']) {
                $this->error('Failed to fetch companies. Aborting sync process.');
                return 1;
            }
            $this->data['companies'] = $companyFetchResult['companies'];
            $projectFetchResult = $this->fetchProjectsAction->handle([
                'client' => $client,
                'command' => $this
            ]);

            if (!$projectFetchResult['success']) {
                $this->error('Failed to fetch projects. Aborting sync process.');
                return 1;
            }
            $this->data['projects'] = $projectFetchResult['projects'];
            $dealFetchResult = $this->fetchDealsAction->handle([
                'client' => $client,
                'command' => $this
            ]);

            if (!$dealFetchResult['success']) {
                $this->error('Failed to fetch deals. Aborting sync process.');
                return 1;
            }
            $this->data['deals'] = $dealFetchResult['deals'];
            $timeEntriesFetchResult = $this->fetchTimeEntriesAction->handle([
                'client' => $client,
                'command' => $this
            ]);

            if (!$timeEntriesFetchResult['success']) {
                $this->error('Failed to fetch time entries. Aborting sync process.');
                return 1;
            }
            $this->data['time_entries'] = $timeEntriesFetchResult['time_entries'];
            $timeEntryVersionsFetchResult = $this->fetchTimeEntryVersionsAction->handle([
                'client' => $client,
                'command' => $this
            ]);

            if (!$timeEntryVersionsFetchResult['success']) {
                $this->error('Failed to fetch time entry versions. Continuing with other data.');
                // We'll continue with other data even if versions fail
            } else {
                $this->data['time_entry_versions'] = $timeEntryVersionsFetchResult['time_entry_versions'];
            }

            // 3. Store data in MySQL
            $this->info('Storing data in database...');
            $storageSuccess = $this->storeDataAction->handle([
                'data' => $this->data,
                'command' => $this
            ]);

            if (!$storageSuccess) {
                $this->error('Failed to store data in database. Aborting sync process.');
                return 1;
            }

            // 4. Validate data integrity
            $this->info('Validating data integrity...');
            $integrityStats = $this->validateDataIntegrityAction->handle([
                'command' => $this
            ]);

            // 5. Report statistics
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $this->info('==== Sync Summary ====');
            $this->info('Companies synced: ' . count($this->data['companies']));
            $this->info('Projects synced: ' . count($this->data['projects']));
            $this->info('Deals synced: ' . count($this->data['deals']));
            $this->info('Time Entries synced: ' . count($this->data['time_entries']));
            $this->info('Time Entry Versions synced: ' . count($this->data['time_entry_versions']));
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
