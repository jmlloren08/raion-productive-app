<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchSubsidiaries extends AbstractAction
{
    /**
     * Define include relationships for subsidiaries
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'bill_from',
        'custom_domain',
        'default_tax_rate',
        'integration'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['bill_from'],
        ['custom_domain'],
        ['default_tax_rate'],
        ['integration'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch subsidiaries from the Productive API
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
                'subsidiaries' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching subsidiaries...');
            }

            $allSubsidiaries = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for subsidiaries
                    $response = $apiClient->get("subsidiaries", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch subsidiaries: ' . $response->body());
                    }

                    $responseBody = $response->json();
                    
                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'subsidiaries' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $subsidiaries = $responseBody['data'];
                    
                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $subsidiaries = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $subsidiaries,
                        'command' => $command
                    ]);

                    $allSubsidiaries = array_merge($allSubsidiaries, $subsidiaries);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($subsidiaries) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching subsidiaries page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($subsidiaries) . " subsidiaries from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch subsidiaries page {$page}: " . $e->getMessage());
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
                        'subsidiaries' => [],
                        'error' => 'Error fetching subsidiaries page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allSubsidiaries) . ' subsidiaries in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allSubsidiaries);
                $this->logRelationshipStats($relationshipStats, count($allSubsidiaries), $command);
            }

            return [
                'success' => true,
                'subsidiaries' => $allSubsidiaries
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in subsidiaries fetch process: " . $e->getMessage());
            }

            Log::error("Error in subsidiaries fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'subsidiaries' => [],
                'error' => 'Error in subsidiaries fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for subsidiaries
     *
     * @param array $subsidiaries
     * @return array
     */
    protected function calculateRelationshipStats(array $subsidiaries): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($subsidiaries as $subsidiary) {
            if (isset($subsidiary['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($subsidiary['relationships'][$relationship]['data']['id']) ||
                        (isset($subsidiary['relationships'][$relationship]['data']) && is_array($subsidiary['relationships'][$relationship]['data']))
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
     * @param int $totalSubsidiaries
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalSubsidiaries, Command $command): void
    {
        if ($totalSubsidiaries > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalSubsidiaries) * 100, 2);
                $command->info("Subsidiaries with {$relationship} relationship: {$count} ({$percentage}%)");
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
