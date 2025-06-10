<?php

namespace App\Actions\Productive;

use App\Actions\Productive\Store\StoreTimeEntry;
use App\Actions\Productive\Store\StoreTimeEntryVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StoreTimeEntriesData extends AbstractAction
{
    protected array $requiredKeys = [
        'time_entries',
        'time_entry_versions'
    ];

    public function __construct(
        private StoreTimeEntry $storeTimeEntryAction,
        private StoreTimeEntryVersion $storeTimeEntryVersionAction,
    ) {}

    /**
     * Store time entries data in the database
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
                empty($data['time_entries']) &&
                empty($data['time_entry_versions'])
            ) {
                if ($command instanceof Command) {
                    $command->warn('No time entries data fetched from Productive API. Skipping storage.');
                }
                return true;
            }

            // Store time entries
            if (!empty($data['time_entries'])) {
                if ($command instanceof Command) {
                    $command->info('Storing time entries...');
                }

                $timeEntriesSuccess = 0;
                $timeEntriesError = 0;
                foreach ($data['time_entries'] as $timeEntryData) {
                    try {
                        $this->storeTimeEntryAction->handle([
                            'timeEntryData' => $timeEntryData,
                            'command' => $command
                        ]);
                        $timeEntriesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store time entry (ID: {$timeEntryData['id']}): " . $e->getMessage());
                        }
                        $timeEntriesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Time Entries: {$timeEntriesSuccess} stored successfully, {$timeEntriesError} failed");
                }
            }

            // Store time entry versions
            if (!empty($data['time_entry_versions'])) {
                if ($command instanceof Command) {
                    $command->info('Storing time entry versions...');
                }

                $timeEntryVersionsSuccess = 0;
                $timeEntryVersionsError = 0;
                foreach ($data['time_entry_versions'] as $timeEntryVersionData) {
                    try {
                        $this->storeTimeEntryVersionAction->handle([
                            'timeEntryVersionData' => $timeEntryVersionData,
                            'command' => $command
                        ]);
                        $timeEntryVersionsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store time entry version (ID: {$timeEntryVersionData['id']}): " . $e->getMessage());
                        }
                        $timeEntryVersionsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Time Entry Versions: {$timeEntryVersionsSuccess} stored successfully, {$timeEntryVersionsError} failed");
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($command instanceof Command) {
                $command->error('Error storing time entries data: ' . $e->getMessage());
            }
            return false;
        }
    }
} 