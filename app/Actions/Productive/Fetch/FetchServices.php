<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchServices extends AbstractAction
{
    /**
     * Define include relationships for services
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'service_type',
        'deal',
        'person',
        'section',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['service_type'],
        ['deal'],
        ['person'],
        ['section'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch services from the Productive API
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
                'services' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching services...');
            }

            $allServices = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("services", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception("Failed to fetch services: " . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'services' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $services = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $services = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $services,
                        'command' => $command
                    ]);

                    $allServices = array_merge($allServices, $services);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($services) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching services page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($services) . " services from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch services page {$page}: " . $e->getMessage());
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
                        'services' => [],
                        'error' => 'Error fetching services page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allServices) . ' services in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allServices);
                $this->logRelationshipStats($relationshipStats, count($allServices), $command);
            }

            return [
                'success' => true,
                'services' => $allServices
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in services fetch process: " . $e->getMessage());
            }

            Log::error("Error in services fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'services' => [],
                'error' => 'Error in services fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for services
     *
     * @param array $services
     * @return array
     */
    protected function calculateRelationshipStats(array $services): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($services as $service) {
            if (isset($service['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($service['relationships'][$relationship]['data']['id'])) {
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
     * @param int $totalServices
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalServices, Command $command): void
    {
        if ($totalServices > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalServices) * 100, 2);
                $command->info("Services with {$relationship} relationship: {$count} ({$percentage}%)");
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
