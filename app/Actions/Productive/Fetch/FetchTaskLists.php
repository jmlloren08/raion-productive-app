<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchTaskLists extends AbstractAction
{
    /**
     * Define include relationships for task lists
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'project',
        'board',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['project'],
        ['board'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch task lists from the Productive API
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
                'task_lists' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching task lists...');
            }

            $allTaskLists = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for task lists
                    $response = $apiClient->get("task_lists", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch task lists: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'task_lists' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $taskLists = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $taskLists = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $taskLists,
                        'command' => $command
                    ]);

                    $allTaskLists = array_merge($allTaskLists, $taskLists);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($taskLists) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching task lists page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($taskLists) . " task lists from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch task lists page {$page}: " . $e->getMessage());
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
                        'task_lists' => [],
                        'error' => 'Error fetching task lists page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allTaskLists) . ' task lists in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allTaskLists);
                $this->logRelationshipStats($relationshipStats, count($allTaskLists), $command);
            }

            return [
                'success' => true,
                'task_lists' => $allTaskLists
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in task lists fetch process: " . $e->getMessage());
            }

            Log::error("Error in task lists fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'task_lists' => [],
                'error' => 'Error in task lists fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for task lists
     *
     * @param array $taskLists
     * @return array
     */
    protected function calculateRelationshipStats(array $taskLists): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($taskLists as $taskList) {
            if (isset($taskList['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($taskList['relationships'][$relationship]['data']['id']) ||
                        (isset($taskList['relationships'][$relationship]['data']) && is_array($taskList['relationships'][$relationship]['data']))
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
     * @param int $totalTaskLists
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalTaskLists, Command $command): void
    {
        if ($totalTaskLists > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalTaskLists) * 100, 2);
                $command->info("Task Lists with {$relationship} relationship: {$count} ({$percentage}%)");
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