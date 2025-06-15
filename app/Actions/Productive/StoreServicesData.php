<?php

namespace App\Actions\Productive;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\Store\StoreServiceType;
use App\Actions\Productive\Store\StoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreServicesData extends AbstractAction
{
    protected array $requiredKeys = [
        'service_types',
        'services'
    ];

    public function __construct(
        private StoreServiceType $storeServiceTypeAction,
        private StoreService $storeServiceAction
    ) {}

    /**
     * Store services data in the database
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
                empty($data['service_types']) &&
                empty($data['services'])
            ) {
                if ($command instanceof Command) {
                    $command->warn('No services data fetched from Productive API. Skipping storage.');
                }
                return true;
            }

            // Store service types first
            if (!empty($data['service_types'])) {
                if ($command instanceof Command) {
                    $command->info('Storing service types...');
                }

                $serviceTypesSuccess = 0;
                $serviceTypesError = 0;
                foreach ($data['service_types'] as $serviceTypeData) {
                    try {
                        $this->storeServiceTypeAction->handle([
                            'serviceTypeData' => $serviceTypeData,
                            'command' => $command
                        ]);
                        $serviceTypesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store service type (ID: {$serviceTypeData['id']}): " . $e->getMessage());
                        }
                        $serviceTypesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Service Types: {$serviceTypesSuccess} stored successfully, {$serviceTypesError} failed");
                }
            }

            // Then store services
            if (!empty($data['services'])) {
                if ($command instanceof Command) {
                    $command->info('Storing services...');
                }

                $servicesSuccess = 0;
                $servicesError = 0;
                foreach ($data['services'] as $serviceData) {
                    try {
                        $this->storeServiceAction->handle([
                            'serviceData' => $serviceData,
                            'command' => $command
                        ]);
                        $servicesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store service (ID: {$serviceData['id']}): " . $e->getMessage());
                        }
                        $servicesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Services: {$servicesSuccess} stored successfully, {$servicesError} failed");
                }
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            if ($command instanceof Command) {
                $command->error('Error storing services data: ' . $e->getMessage());
            }
            return false;
        }
    }
} 