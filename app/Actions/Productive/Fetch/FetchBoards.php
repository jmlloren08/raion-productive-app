<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchBoards extends AbstractAction
{
    /**
     * Define include relationships for boards
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'project',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['project'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch boards from the Productive API
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
                'boards' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching boards...');
            }

            $allBoards = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for boards
                    $response = $apiClient->get("boards", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch boards: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'boards' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $boards = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $boards = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $boards,
                        'command' => $command
                    ]);

                    $allBoards = array_merge($allBoards, $boards);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($boards) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching boards page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($boards) . " boards from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch boards page {$page}: " . $e->getMessage());
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
                        'boards' => [],
                        'error' => 'Error fetching boards page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allBoards) . ' boards in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allBoards);
                $this->logRelationshipStats($relationshipStats, count($allBoards), $command);
            }

            return [
                'success' => true,
                'boards' => $allBoards
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in boards fetch process: " . $e->getMessage());
            }

            Log::error("Error in boards fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'boards' => [],
                'error' => 'Error in boards fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for boards
     *
     * @param array $boards
     * @return array
     */
    protected function calculateRelationshipStats(array $boards): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($boards as $board) {
            if (isset($board['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($board['relationships'][$relationship]['data']['id']) ||
                        (isset($board['relationships'][$relationship]['data']) && is_array($board['relationships'][$relationship]['data']))
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
     * @param int $totalBoards
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalBoards, Command $command): void
    {
        if ($totalBoards > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalBoards) * 100, 2);
                $command->info("Boards with {$relationship} relationship: {$count} ({$percentage}%)");
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