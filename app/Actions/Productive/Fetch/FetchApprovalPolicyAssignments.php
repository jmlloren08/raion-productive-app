<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchApprovalPolicyAssignments extends AbstractAction
{
    /**
     * Define include relationships for approval policy assignments
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'person',
        'deal',
        'approval_policy',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['person'],
        ['deal'],
        ['approval_policy'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch approval policy assignments from the Productive API
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
                'approval_policy_assignments' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching approval policy assignments...');
            }

            $allAssignments = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for approval policy assignments
                    $response = $apiClient->get("approval_policy_assignments", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch approval policy assignments: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'approval_policy_assignments' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $assignments = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $assignments = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $assignments,
                        'command' => $command
                    ]);

                    $allAssignments = array_merge($allAssignments, $assignments);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($assignments) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching approval policy assignments page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($assignments) . " approval policy assignments from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch approval policy assignments page {$page}: " . $e->getMessage());
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
                        'approval_policy_assignments' => [],
                        'error' => 'Error fetching approval policy assignments page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allAssignments) . ' approval policy assignments in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allAssignments);
                $this->logRelationshipStats($relationshipStats, count($allAssignments), $command);
            }

            return [
                'success' => true,
                'approval_policy_assignments' => $allAssignments
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in approval policy assignments fetch process: " . $e->getMessage());
            }

            Log::error("Error in approval policy assignments fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'approval_policy_assignments' => [],
                'error' => 'Error in approval policy assignments fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for approval policy assignments
     *
     * @param array $assignments
     * @return array
     */
    protected function calculateRelationshipStats(array $assignments): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($assignments as $assignment) {
            if (isset($assignment['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($assignment['relationships'][$relationship]['data']['id']) ||
                        (isset($assignment['relationships'][$relationship]['data']) && is_array($assignment['relationships'][$relationship]['data']))
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
     * @param int $totalAssignments
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalAssignments, Command $command): void
    {
        if ($totalAssignments > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalAssignments) * 100, 2);
                $command->info("Approval Policy Assignments with {$relationship} relationship: {$count} ({$percentage}%)");
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
