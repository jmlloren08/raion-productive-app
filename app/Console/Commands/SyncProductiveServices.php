<?php

namespace App\Console\Commands;

use App\Actions\Productive\InitializeClient;
use App\Actions\Productive\Fetch\FetchServiceTypes;
use App\Actions\Productive\Fetch\FetchServices;
use App\Actions\Productive\StoreServicesData;
use App\Actions\Productive\ValidateDataIntegrity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProductiveServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:productive-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync service types and services from Productive.io API';

    private $data = [
        'service_types' => [],
        'services' => [],
    ];

    /**
     * Create a new command instance.
     */
    public function __construct(
        private InitializeClient $initializeClientAction,
        private FetchServiceTypes $fetchServiceTypesAction,
        private FetchServices $fetchServicesAction,
        private StoreServicesData $storeServicesDataAction,
        private ValidateDataIntegrity $validateDataIntegrityAction
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Productive.io services sync...');
        $startTime = microtime(true);

        try {
            // Debug: Output configuration values
            $this->info('API URL: ' . config('services.productive.api_url'));
            $this->info('API Token: ' . (config('services.productive.api_token') ? 'Set' : 'Not set'));
            $this->info('Organization ID: ' . config('services.productive.organization_id'));

            // Initialize API client
            $apiClient = $this->initializeClientAction->handle();
            $this->info('Fetching services data from Productive API...');

            // Fetch service types
            $serviceTypes = $this->fetchServiceTypesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$serviceTypes['success']) {
                $this->error('Failed to fetch service types: ' . ($serviceTypes['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['service_types'] = $serviceTypes['service_types'];

            // Fetch services
            $services = $this->fetchServicesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$services['success']) {
                $this->error('Failed to fetch services: ' . ($services['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['services'] = $services['services'];

            // Store data in MySQL
            $this->info('Storing services data in database...');
            $storageSuccess = $this->storeServicesDataAction->handle([
                'data' => $this->data,
                'command' => $this
            ]);

            if (!$storageSuccess) {
                $this->error('Failed to store services data in database. Aborting sync process.');
                return 1;
            }

            // Validate data integrity
            $this->info('Validating services data integrity...');
            $this->validateDataIntegrityAction->handle([
                'command' => $this
            ]);

            // Report statistics
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            $this->info('==== Services Sync Summary ====');
            $this->info('Service Types synced: ' . count($this->data['service_types']));
            $this->info('Services synced: ' . count($this->data['services']));
            $this->info('Execution time: ' . $executionTime . ' seconds');
            $this->info('Services sync completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Services sync failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Productive services sync error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 