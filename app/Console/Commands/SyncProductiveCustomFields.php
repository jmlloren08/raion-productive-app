<?php

namespace App\Console\Commands;

use App\Actions\Productive\InitializeClient;
use App\Actions\Productive\Fetch\FetchCustomFields;
use App\Actions\Productive\Fetch\FetchCustomFieldOptions;
use App\Actions\Productive\StoreCustomFieldsData;
use App\Actions\Productive\ValidateDataIntegrity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProductiveCustomFields extends Command
{
    protected $signature = 'sync:productive-custom-fields';
    protected $description = 'Sync custom fields and custom field options from Productive.io API';
    private $data = [
        'custom_fields' => [],
        'custom_field_options' => [],
    ];

    public function __construct(
        private InitializeClient $initializeClientAction,
        private FetchCustomFields $fetchCustomFieldsAction,
        private FetchCustomFieldOptions $fetchCustomFieldOptionsAction,
        private StoreCustomFieldsData $storeCustomFieldsDataAction,
        private ValidateDataIntegrity $validateDataIntegrityAction
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting Productive.io custom fields sync...');
        $startTime = microtime(true);

        try {
            // Debug: Output configuration values
            $this->info('API URL: ' . config('services.productive.api_url'));
            $this->info('API Token: ' . (config('services.productive.api_token') ? 'Set' : 'Not set'));
            $this->info('Organization ID: ' . config('services.productive.organization_id'));

            // Initialize API client
            $apiClient = $this->initializeClientAction->handle();
            $this->info('Fetching custom fields data from Productive API...');

            // Fetch custom fields
            $customFields = $this->fetchCustomFieldsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$customFields['success']) {
                $this->error('Failed to fetch custom fields: ' . ($customFields['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['custom_fields'] = $customFields['custom_fields'];

            // Fetch custom field options
            $customFieldOptions = $this->fetchCustomFieldOptionsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$customFieldOptions['success']) {
                $this->error('Failed to fetch custom field options: ' . ($customFieldOptions['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['custom_field_options'] = $customFieldOptions['custom_field_options'];

            // Store data in MySQL
            $this->info('Storing custom fields data in database...');
            $storageSuccess = $this->storeCustomFieldsDataAction->handle([
                'data' => $this->data,
                'command' => $this
            ]);

            if (!$storageSuccess) {
                $this->error('Failed to store custom fields data in database. Aborting sync process.');
                return 1;
            }

            // Validate data integrity
            $this->info('Validating custom fields data integrity...');
            $this->validateDataIntegrityAction->handle([
                'command' => $this
            ]);

            // Report statistics
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            $this->info('==== Custom Fields Sync Summary ====');
            $this->info('Custom Fields synced: ' . count($this->data['custom_fields']));
            $this->info('Custom Field Options synced: ' . count($this->data['custom_field_options']));
            $this->info('Execution time: ' . $executionTime . ' seconds');
            $this->info('Custom fields sync completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Custom fields sync failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Productive custom fields sync error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 