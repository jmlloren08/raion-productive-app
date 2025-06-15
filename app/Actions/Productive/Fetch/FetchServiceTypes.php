<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchServiceTypes extends AbstractAction
{
    /**
     * Define include relationships for service types
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'assignees',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['assignees'],  // Fallback to just 'assignees'
        []  // Empty array means no includes
    ];

    /**
     * Fetch service types from the Productive API
     *
     * @param array $parameters
     * @return array
     */
    public function handle(array $parameters = []): array
    {
        $command = $parameters['command'] ?? null;
        $apiClient = $parameters['apiClient'] ?? null;

        if (!$apiClient) {
            return [
                'success' => false,
                'service_types' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching service types...');
            }

            $allServiceTypes = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("service_types", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if(!$response->successful()) {
                        throw new \Exception("Failed to fetch service types: " . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'service_types' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $serviceTypes = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $serviceTypes = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $serviceTypes,
                        'command' => $command
                    ]);

                    $allServiceTypes = array_merge($allServiceTypes, $serviceTypes);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($serviceTypes) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching service types page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($serviceTypes) . " service types from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch service types page {$page}: " . $e->getMessage());
                    }

                    // If 'include' parameter is causing problems, try with fallback includes
                    if (strpos($e->getMessage(), 'include') !== false) {
                        foreach ($this->fallbackIncludes as $fallbackInclude) {
                            if (implode(',', $fallbackInclude) !== $includeParam) {
                                if ($command instanceof Command) {
                                    $command->warn("Retrying with include parameter: " . ($fallbackInclude ? implode(',', $fallbackInclude) : 'none'));
                                }
                                $includeParam = implode(',', $fallbackInclude);
                                continue 2; // Continue the outer while loop
                            }
                        }
                    }

                    return [
                        'success' => false,
                        'service_types' => [],
                        'error' => 'Error fetching service types page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allServiceTypes) . ' service types in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allServiceTypes);
                $this->logRelationshipStats($relationshipStats, count($allServiceTypes), $command);
            }

            return [
                'success' => true,
                'service_types' => $allServiceTypes
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in service types fetch process: " . $e->getMessage());
            }

            Log::error("Error in service types fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'service_types' => [],
                'error' => 'Error in service types fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for service types
     *
     * @param array $serviceTypes
     * @return array
     */
    protected function calculateRelationshipStats(array $serviceTypes): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($serviceTypes as $type) {
            if (isset($type['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($type['relationships'][$relationship]['data']['id'])) {
                        $stats[$relationship]++;
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Log relationship statistics to the command output
     *
     * @param array $stats
     * @param int $totalTypes
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalTypes, Command $command): void
    {
        if ($totalTypes > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalTypes) * 100, 2);
                $command->info("Service types with {$relationship} relationship: {$count} ({$percentage}%)");
            }
        }
    }

    /**
     * Log included data types for debugging
     *
     * @param array $included
     * @param int $page
     * @param Command $command
     * @return void
     */
    protected function logIncludedData(array $included, int $page, Command $command): void
    {
        $includedTypes = [];
        foreach ($included as $include) {
            $type = $include['type'] ?? 'unknown';
            $includedTypes[$type] = ($includedTypes[$type] ?? 0) + 1;
        }
        $command->info("Page {$page} included data: " . json_encode($includedTypes));
    }
} 