<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchDealStatus extends AbstractAction
{
    /**
     * Define include relationships for deal statuses
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'pipeline'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['pipeline'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch deal statuses from the Productive API
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
                'deal_statuses' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching deal statuses...');
            }

            $allDealStatuses = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for deal statuses
                    $response = $apiClient->get("deal_statuses", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch deal statuses: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'deal_statuses' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $dealStatuses = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $dealStatuses = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $dealStatuses,
                        'command' => $command
                    ]);

                    $allDealStatuses = array_merge($allDealStatuses, $dealStatuses);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($dealStatuses) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching deal statuses page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($dealStatuses) . " deal statuses from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch deal statuses page {$page}: " . $e->getMessage());
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
                        'deal_statuses' => [],
                        'error' => 'Error fetching deal statuses page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allDealStatuses) . ' deal statuses in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allDealStatuses);
                $this->logRelationshipStats($relationshipStats, count($allDealStatuses), $command);
            }

            return [
                'success' => true,
                'deal_statuses' => $allDealStatuses
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in deal statuses fetch process: " . $e->getMessage());
            }

            Log::error("Error in deal statuses fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'deal_statuses' => [],
                'error' => 'Error in deal statuses fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for deal statuses
     *
     * @param array $dealStatuses
     * @return array
     */
    protected function calculateRelationshipStats(array $dealStatuses): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($dealStatuses as $dealStatus) {
            if (isset($dealStatus['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($dealStatus['relationships'][$relationship]['data']['id']) ||
                        (isset($dealStatus['relationships'][$relationship]['data']) && is_array($dealStatus['relationships'][$relationship]['data']))
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
     * @param int $totalDealStatuses
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalDealStatuses, Command $command): void
    {
        if ($totalDealStatuses > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalDealStatuses) * 100, 2);
                $command->info("Deal statuses with {$relationship} relationship: {$count} ({$percentage}%)");
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
