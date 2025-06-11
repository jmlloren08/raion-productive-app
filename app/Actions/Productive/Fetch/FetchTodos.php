<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchTodos extends AbstractAction
{
    /**
     * Define include relationships for todos
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'assignee',
        'deal',
        'task'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['assignee'],
        ['deal'],
        ['task'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch todos from the Productive API
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
                'todos' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching todos...');
            }

            $allTodos = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for todos
                    $response = $apiClient->get("todos", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch todos: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'todos' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $todos = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $todos = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $todos,
                        'command' => $command
                    ]);

                    $allTodos = array_merge($allTodos, $todos);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($todos) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching todos page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($todos) . " todos from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch todos page {$page}: " . $e->getMessage());
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
                        'todos' => [],
                        'error' => 'Error fetching todos page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allTodos) . ' todos in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allTodos);
                $this->logRelationshipStats($relationshipStats, count($allTodos), $command);
            }

            return [
                'success' => true,
                'todos' => $allTodos
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in todos fetch process: " . $e->getMessage());
            }

            Log::error("Error in todos fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'todos' => [],
                'error' => 'Error in todos fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for todos
     *
     * @param array $todos
     * @return array
     */
    protected function calculateRelationshipStats(array $todos): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($todos as $todo) {
            if (isset($todo['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($todo['relationships'][$relationship]['data']['id']) ||
                        (isset($todo['relationships'][$relationship]['data']) && is_array($todo['relationships'][$relationship]['data']))
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
     * @param int $totalTodos
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalTodos, Command $command): void
    {
        if ($totalTodos > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalTodos) * 100, 2);
                $command->info("Todos with {$relationship} relationship: {$count} ({$percentage}%)");
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