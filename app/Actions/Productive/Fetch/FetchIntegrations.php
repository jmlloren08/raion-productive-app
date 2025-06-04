<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchIntegrations extends AbstractAction
{
    /**
     * Define include relationships for integrations
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'subsidiary',
        'project',
        'creator',
        'deal',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['subsidiary'],
        ['project'],
        ['creator'],
        ['deal'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch integrations from the Productive API
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
                'integrations' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching integrations...');
            }

            $allIntegrations = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("integrations", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch integrations: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'integrations' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $integrations = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $integrations = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $integrations,
                        'command' => $command
                    ]);

                    $allIntegrations = array_merge($allIntegrations, $integrations);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($integrations) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching integrations page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($integrations) . " integrations from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch integrations page {$page}: " . $e->getMessage());
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
                        'integrations' => [],
                        'error' => 'Error fetching integrations page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allIntegrations) . ' integrations in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allIntegrations);
                $this->logRelationshipStats($relationshipStats, count($allIntegrations), $command);
            }

            return [
                'success' => true,
                'integrations' => $allIntegrations
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in integrations fetch process: " . $e->getMessage());
            }

            Log::error("Error in integrations fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'integrations' => [],
                'error' => 'Error in integrations fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for integrations
     *
     * @param array $integrations
     * @return array
     */
    protected function calculateRelationshipStats(array $integrations): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($integrations as $integration) {
            if (isset($integration['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($integration['relationships'][$relationship]['data']['id']) ||
                        (isset($integration['relationships'][$relationship]['data']) && is_array($integration['relationships'][$relationship]['data']))
                    ) {
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
     * @param int $totalIntegrations
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalIntegrations, Command $command): void
    {
        if ($totalIntegrations > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalIntegrations) * 100, 2);
                $command->info("Integrations with {$relationship} relationship: {$count} ({$percentage}%)");
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