<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchContracts extends AbstractAction
{
    /**
     * Define include relationships for contracts
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'template',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['template'],  // Fallback to template only
        []  // Empty array means no includes
    ];

    /**
     * Fetch contracts from the Productive API
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
                'contracts' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching contracts...');
            }

            $allContracts = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("contracts", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch contracts: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'contracts' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $contracts = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $contracts = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $contracts,
                        'command' => $command
                    ]);

                    $allContracts = array_merge($allContracts, $contracts);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($contracts) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching contracts page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($contracts) . " contracts from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch contracts page {$page}: " . $e->getMessage());
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
                        'contracts' => [],
                        'error' => 'Error fetching contracts page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allContracts) . ' contracts in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allContracts);
                $this->logRelationshipStats($relationshipStats, count($allContracts), $command);
            }

            return [
                'success' => true,
                'contracts' => $allContracts
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in contracts fetch process: " . $e->getMessage());
            }

            Log::error("Error in contracts fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'contracts' => [],
                'error' => 'Error in contracts fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for contracts
     *
     * @param array $contracts
     * @return array
     */
    protected function calculateRelationshipStats(array $contracts): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($contracts as $contract) {
            if (isset($contract['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($contract['relationships'][$relationship]['data']['id']) ||
                        (isset($contract['relationships'][$relationship]['data']) && is_array($contract['relationships'][$relationship]['data']))
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
     * @param int $totalContracts
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalContracts, Command $command): void
    {
        if ($totalContracts > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalContracts) * 100, 2);
                $command->info("Contracts with {$relationship} relationship: {$count} ({$percentage}%)");
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
