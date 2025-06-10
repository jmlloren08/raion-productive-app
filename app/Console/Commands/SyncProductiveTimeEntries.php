<?php

namespace App\Console\Commands;

use App\Actions\Productive\InitializeClient;
use App\Actions\Productive\Fetch\FetchTimeEntries;
use App\Actions\Productive\Fetch\FetchTimeEntryVersions;
use App\Actions\Productive\StoreTimeEntriesData;
use App\Actions\Productive\ValidateDataIntegrity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProductiveTimeEntries extends Command
{
    protected $signature = 'sync:productive-time-entries';
    protected $description = 'Sync time entries and time entry versions from Productive.io API';
    private $data = [
        'time_entries' => [],
        'time_entry_versions' => [],
    ];

    public function __construct(
        private InitializeClient $initializeClientAction,
        private FetchTimeEntries $fetchTimeEntriesAction,
        private FetchTimeEntryVersions $fetchTimeEntryVersionsAction,
        private StoreTimeEntriesData $storeTimeEntriesDataAction,
        private ValidateDataIntegrity $validateDataIntegrityAction
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting Productive.io time entries sync...');
        $startTime = microtime(true);

        try {
            // Debug: Output configuration values
            $this->info('API URL: ' . config('services.productive.api_url'));
            $this->info('API Token: ' . (config('services.productive.api_token') ? 'Set' : 'Not set'));
            $this->info('Organization ID: ' . config('services.productive.organization_id'));

            // Initialize API client
            $apiClient = $this->initializeClientAction->handle();
            $this->info('Fetching time entries data from Productive API...');

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
            $this->info('Storing time entries data in database...');
            $storageSuccess = $this->storeTimeEntriesDataAction->handle([
                'data' => $this->data,
                'command' => $this
            ]);

            if (!$storageSuccess) {
                $this->error('Failed to store time entries data in database. Aborting sync process.');
                return 1;
            }

            // Validate data integrity
            $this->info('Validating time entries data integrity...');
            $this->validateDataIntegrityAction->handle([
                'command' => $this
            ]);

            // Report statistics
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            $this->info('==== Time Entries Sync Summary ====');
            $this->info('Time Entries synced: ' . count($this->data['time_entries']));
            $this->info('Time Entry Versions synced: ' . count($this->data['time_entry_versions']));
            $this->info('Execution time: ' . $executionTime . ' seconds');
            $this->info('Time entries sync completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Time entries sync failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Productive time entries sync error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 