<?php

namespace App\Console\Commands;

use App\Actions\Productive\InitializeClient;
use App\Actions\Productive\Fetch\FetchPaymentReminderSequences;
use App\Actions\Productive\Fetch\FetchPaymentReminders;
use App\Actions\Productive\StorePrsData;
use App\Actions\Productive\ValidateDataIntegrity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProductivePrs extends Command
{
    protected $signature = 'sync:productive-prs';
    protected $description = 'Sync payment reminder sequences and payment reminders from Productive.io API';
    private $data = [
        'payment_reminder_sequences' => [],
        'payment_reminders' => [],
    ];

    public function __construct(
        private InitializeClient $initializeClientAction,
        private FetchPaymentReminderSequences $fetchPaymentReminderSequencesAction,
        private FetchPaymentReminders $fetchPaymentRemindersAction,
        private StorePrsData $storePrsDataAction,
        private ValidateDataIntegrity $validateDataIntegrityAction
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting Productive.io payment reminder sequences sync...');
        $startTime = microtime(true);

        try {
            // Debug: Output configuration values
            $this->info('API URL: ' . config('services.productive.api_url'));
            $this->info('API Token: ' . (config('services.productive.api_token') ? 'Set' : 'Not set'));
            $this->info('Organization ID: ' . config('services.productive.organization_id'));

            // Initialize API client
            $apiClient = $this->initializeClientAction->handle();
            $this->info('Fetching payment reminder sequences data from Productive API...');

            // Fetch payment reminder sequences
            $sequences = $this->fetchPaymentReminderSequencesAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$sequences['success']) {
                $this->error('Failed to fetch payment reminder sequences: ' . ($sequences['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['payment_reminder_sequences'] = $sequences['payment_reminder_sequences'];

            // Fetch payment reminders
            $reminders = $this->fetchPaymentRemindersAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);

            if (!$reminders['success']) {
                $this->error('Failed to fetch payment reminders: ' . ($reminders['error'] ?? 'Unknown error'));
                return 1;
            }

            $this->data['payment_reminders'] = $reminders['payment_reminders'];

            // Store data in MySQL
            $this->info('Storing payment reminder sequences data in database...');
            $storageSuccess = $this->storePrsDataAction->handle([
                'data' => $this->data,
                'command' => $this
            ]);

            if (!$storageSuccess) {
                $this->error('Failed to store payment reminder sequences data in database. Aborting sync process.');
                return 1;
            }

            // Validate data integrity
            $this->info('Validating payment reminder sequences data integrity...');
            $this->validateDataIntegrityAction->handle([
                'command' => $this
            ]);

            // Report statistics
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            $this->info('==== Payment Reminder Sequences Sync Summary ====');
            $this->info('Payment Reminder Sequences synced: ' . count($this->data['payment_reminder_sequences']));
            $this->info('Payment Reminders synced: ' . count($this->data['payment_reminders']));
            $this->info('Execution time: ' . $executionTime . ' seconds');
            $this->info('Payment reminder sequences sync completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Payment reminder sequences sync failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Productive payment reminder sequences sync error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 