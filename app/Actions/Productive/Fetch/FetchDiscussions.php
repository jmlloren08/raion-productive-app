<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchDiscussions extends AbstractAction
{
    /**
     * Define include relationships for discussions
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'page',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['page'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch discussions from the Productive API
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
                'discussions' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching discussions...');
            }

            $allDiscussions = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for discussions
                    $response = $apiClient->get("discussions", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch discussions: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'discussions' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $discussions = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $discussions = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $discussions,
                        'command' => $command
                    ]);

                    $allDiscussions = array_merge($allDiscussions, $discussions);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($discussions) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching discussions page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($discussions) . " discussions from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch discussions page {$page}: " . $e->getMessage());
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
                        'discussions' => [],
                        'error' => 'Error fetching discussions page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allDiscussions) . ' discussions in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allDiscussions);
                $this->logRelationshipStats($relationshipStats, count($allDiscussions), $command);
            }

            return [
                'success' => true,
                'discussions' => $allDiscussions
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in discussions fetch process: " . $e->getMessage());
            }

            Log::error("Error in discussions fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'discussions' => [],
                'error' => 'Error in discussions fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for discussions
     *
     * @param array $discussions
     * @return array
     */
    protected function calculateRelationshipStats(array $discussions): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($discussions as $discussion) {
            if (isset($discussion['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($discussion['relationships'][$relationship]['data']['id']) ||
                        (isset($discussion['relationships'][$relationship]['data']) && is_array($discussion['relationships'][$relationship]['data']))
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
     * @param int $totalDiscussions
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalDiscussions, Command $command): void
    {
        if ($totalDiscussions > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalDiscussions) * 100, 2);
                $command->info("Discussions with {$relationship} relationship: {$count} ({$percentage}%)");
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