<?php

namespace App\Actions\Productive;

use App\Actions\Productive\Store\StoreCustomField;
use App\Actions\Productive\Store\StoreCustomFieldOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StoreCustomFieldsData extends AbstractAction
{
    protected array $requiredKeys = [
        'custom_fields',
        'custom_field_options'
    ];

    public function __construct(
        private StoreCustomField $storeCustomFieldAction,
        private StoreCustomFieldOption $storeCustomFieldOptionAction,
    ) {}

    /**
     * Store custom fields data in the database
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
                empty($data['custom_fields']) &&
                empty($data['custom_field_options'])
            ) {
                if ($command instanceof Command) {
                    $command->warn('No custom fields data fetched from Productive API. Skipping storage.');
                }
                return true;
            }

            // Store custom fields
            if (!empty($data['custom_fields'])) {
                if ($command instanceof Command) {
                    $command->info('Storing custom fields...');
                }

                $customFieldsSuccess = 0;
                $customFieldsError = 0;
                foreach ($data['custom_fields'] as $customFieldData) {
                    try {
                        $this->storeCustomFieldAction->handle([
                            'customFieldData' => $customFieldData,
                            'command' => $command
                        ]);
                        $customFieldsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store custom field (ID: {$customFieldData['id']}): " . $e->getMessage());
                        }
                        $customFieldsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Custom Fields: {$customFieldsSuccess} stored successfully, {$customFieldsError} failed");
                }
            }

            // Store custom field options
            if (!empty($data['custom_field_options'])) {
                if ($command instanceof Command) {
                    $command->info('Storing custom field options...');
                }

                $customFieldOptionsSuccess = 0;
                $customFieldOptionsError = 0;
                foreach ($data['custom_field_options'] as $customFieldOptionData) {
                    try {
                        $this->storeCustomFieldOptionAction->handle([
                            'customFieldOptionData' => $customFieldOptionData,
                            'command' => $command
                        ]);
                        $customFieldOptionsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store custom field option (ID: {$customFieldOptionData['id']}): " . $e->getMessage());
                        }
                        $customFieldOptionsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Custom Field Options: {$customFieldOptionsSuccess} stored successfully, {$customFieldOptionsError} failed");
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($command instanceof Command) {
                $command->error('Error storing custom fields data: ' . $e->getMessage());
            }
            return false;
        }
    }
} 