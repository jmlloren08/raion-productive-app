<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchCustomDomains extends AbstractAction
{
    /**
     * Define include relationships for custom domains
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'subsidiaries',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['subsidiaries'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch custom domains from the Productive API
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
                'custom_domains' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching custom domains...');
            }

            $allCustomDomains = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("custom_domains", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch custom domains: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'custom_domains' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $customDomains = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $customDomains = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $customDomains,
                        'command' => $command
                    ]);

                    $allCustomDomains = array_merge($allCustomDomains, $customDomains);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($customDomains) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching custom domains page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($customDomains) . " custom domains from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch custom domains page {$page}: " . $e->getMessage());
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
                        'custom_domains' => [],
                        'error' => 'Error fetching custom domains page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allCustomDomains) . ' custom domains in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allCustomDomains);
                $this->logRelationshipStats($relationshipStats, count($allCustomDomains), $command);
            }

            return [
                'success' => true,
                'custom_domains' => $allCustomDomains
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in custom domains fetch process: " . $e->getMessage());
            }

            Log::error("Error in custom domains fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'custom_domains' => [],
                'error' => 'Error in custom domains fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for custom domains
     *
     * @param array $customDomains
     * @return array
     */
    protected function calculateRelationshipStats(array $customDomains): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($customDomains as $customDomain) {
            if (isset($customDomain['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($customDomain['relationships'][$relationship]['data']['id']) ||
                        (isset($customDomain['relationships'][$relationship]['data']) && is_array($customDomain['relationships'][$relationship]['data']))
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
     * @param int $totalCustomDomains
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalCustomDomains, Command $command): void
    {
        if ($totalCustomDomains > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalCustomDomains) * 100, 2);
                $command->info("Custom domains with {$relationship} relationship: {$count} ({$percentage}%)");
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
