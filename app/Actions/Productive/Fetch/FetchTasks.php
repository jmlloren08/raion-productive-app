<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchTasks extends AbstractAction
{
    /**
     * Define include relationships for tasks
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'project',
        'creator',
        'assignee',
        'last_actor',
        'task_list',
        'parent_task',
        'workflow_status',
        'attachments',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['project'],
        ['creator'],
        ['assignee'],
        ['last_actor'],
        ['task_list'],
        ['parent_task'],
        ['workflow_status'],
        ['attachments'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch tasks from the Productive API
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
                'tasks' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching tasks...');
            }

            $allTasks = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for tasks
                    $response = $apiClient->get("tasks", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch tasks: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'tasks' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $tasks = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $tasks = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $tasks,
                        'command' => $command
                    ]);

                    $allTasks = array_merge($allTasks, $tasks);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($tasks) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching tasks page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($tasks) . " tasks from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch tasks page {$page}: " . $e->getMessage());
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
                        'tasks' => [],
                        'error' => 'Error fetching tasks page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allTasks) . ' tasks in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allTasks);
                $this->logRelationshipStats($relationshipStats, count($allTasks), $command);
            }

            return [
                'success' => true,
                'tasks' => $allTasks
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in tasks fetch process: " . $e->getMessage());
            }

            Log::error("Error in tasks fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'tasks' => [],
                'error' => 'Error in tasks fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for tasks
     *
     * @param array $tasks
     * @return array
     */
    protected function calculateRelationshipStats(array $tasks): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($tasks as $task) {
            if (isset($task['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($task['relationships'][$relationship]['data']['id']) ||
                        (isset($task['relationships'][$relationship]['data']) && is_array($task['relationships'][$relationship]['data']))
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
     * @param int $totalTasks
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalTasks, Command $command): void
    {
        if ($totalTasks > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalTasks) * 100, 2);
                $command->info("Tasks with {$relationship} relationship: {$count} ({$percentage}%)");
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
