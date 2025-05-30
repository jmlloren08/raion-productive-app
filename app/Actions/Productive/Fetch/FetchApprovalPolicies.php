<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchApprovalPolicies extends AbstractAction
{
    /**
     * Define include relationships for approval policies
     * 
     * @var array
     */
    protected array $includeRelationships = [
        '',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        []  // Empty array means no includes
    ];

    /**
     * Fetch approval policies from the Productive API
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
                'approval_policies' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching approval policies...');
            }

            $allPolicies = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for approval policies
                    $response = $apiClient->get("approval_policies", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch approval policies: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'approval_policies' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $policies = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $policies = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $policies,
                        'command' => $command
                    ]);

                    $allPolicies = array_merge($allPolicies, $policies);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($policies) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching approval policies page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($policies) . " approval policies from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch approval policies page {$page}: " . $e->getMessage());
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
                        'approval_policies' => [],
                        'error' => 'Error fetching approval policies page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allPolicies) . ' approval policies in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allPolicies);
                $this->logRelationshipStats($relationshipStats, count($allPolicies), $command);
            }

            return [
                'success' => true,
                'approval_policies' => $allPolicies
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in approval policies fetch process: " . $e->getMessage());
            }

            Log::error("Error in approval policies fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'approval_policies' => [],
                'error' => 'Error in approval policies fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for approval policies
     *
     * @param array $policies
     * @return array
     */
    protected function calculateRelationshipStats(array $policies): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($policies as $policy) {
            if (isset($policy['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($policy['relationships'][$relationship]['data']['id']) ||
                        (isset($policy['relationships'][$relationship]['data']) && is_array($policy['relationships'][$relationship]['data']))
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
     * @param int $totalPolicies
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalPolicies, Command $command): void
    {
        if ($totalPolicies > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalPolicies) * 100, 2);
                $command->info("Approval Policies with {$relationship} relationship: {$count} ({$percentage}%)");
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