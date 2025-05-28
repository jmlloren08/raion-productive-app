<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchWorkflows extends AbstractAction
{
    /**
     * Define include relationships for workflows
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'workflow_statuses'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['workflow_statuses'],
        [] // Empty array means no includes
    ];

    /**
     * Fetch workflows from the Productive API
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
                'workflows' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching workflows from Productive API...');
            }

            $allWorkflows = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get('/workflows', [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ]);

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch workflows: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'workflows' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $workflows = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $workflows = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $workflows,
                        'command' => $command
                    ]);

                    $allWorkflows = array_merge($allWorkflows, $workflows);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($workflows) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching workflows page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($workflows) . " workflows from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch workflows page {$page}: " . $e->getMessage());
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
                        'workflows' => [],
                        'error' => 'Error fetching workflows page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allWorkflows) . ' workflows in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allWorkflows);
                $this->logRelationshipStats($relationshipStats, count($allWorkflows), $command);
            }

            return [
                'success' => true,
                'workflows' => $allWorkflows
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in workflows fetch process: " . $e->getMessage());
            }

            Log::error("Error in workflows fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'workflows' => [],
                'error' => 'Error in workflows fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for workflows
     *
     * @param array $workflows
     * @return array
     */
    protected function calculateRelationshipStats(array $workflows): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($workflows as $workflow) {
            if (isset($workflow['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($workflow['relationships'][$relationship]['data']['id']) ||
                        (isset($workflow['relationships'][$relationship]['data']) && is_array($workflow['relationships'][$relationship]['data']))
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
     * @param int $totalWorkflows
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalWorkflows, Command $command): void
    {
        if ($totalWorkflows > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalWorkflows) * 100, 2);
                $command->info("Workflows with {$relationship} relationship: {$count} ({$percentage}%)");
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