<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchWorkflowStatuses extends AbstractAction
{
    /**
     * Define include relationships for workflow statuses
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'workflow',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['workflow'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch workflow statuses from the Productive API
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
                'workflow_statuses' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching workflow statuses...');
            }

            $allWorkflowStatuses = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("workflow_statuses", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if(!$response->successful()) {
                        throw new \Exception("Failed to fetch workflow statuses: " . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'workflow_statuses' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $workflowStatuses = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $workflowStatuses = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $workflowStatuses,
                        'command' => $command
                    ]);

                    $allWorkflowStatuses = array_merge($allWorkflowStatuses, $workflowStatuses);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($workflowStatuses) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching workflow statuses page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($workflowStatuses) . " workflow statuses from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch workflow statuses page {$page}: " . $e->getMessage());
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
                        'workflow_statuses' => [],
                        'error' => 'Error fetching workflow statuses page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allWorkflowStatuses) . ' workflow statuses in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allWorkflowStatuses);
                $this->logRelationshipStats($relationshipStats, count($allWorkflowStatuses), $command);
            }

            return [
                'success' => true,
                'workflow_statuses' => $allWorkflowStatuses
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in workflow statuses fetch process: " . $e->getMessage());
            }

            Log::error("Error in workflow statuses fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'workflow_statuses' => [],
                'error' => 'Error in workflow statuses fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for workflow statuses
     *
     * @param array $workflowStatuses
     * @return array
     */
    protected function calculateRelationshipStats(array $workflowStatuses): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($workflowStatuses as $status) {
            if (isset($status['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($status['relationships'][$relationship]['data']['id'])) {
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
     * @param int $totalEntries
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalEntries, Command $command): void
    {
        if ($totalEntries > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalEntries) * 100, 2);
                $command->info("Workflow statuses with {$relationship} relationship: {$count} ({$percentage}%)");
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