<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchPipelines extends AbstractAction
{
    /**
     * Define include relationships for pipelines
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'creator',
        'updater',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['creator'], 
        ['updater'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch pipelines from the Productive API
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
                'pipelines' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching pipelines...');
            }

            $allPipelines = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for pipelines
                    $response = $apiClient->get("pipelines", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch pipelines: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'pipelines' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $pipelines = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $pipelines = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $pipelines,
                        'command' => $command
                    ]);

                    $allPipelines = array_merge($allPipelines, $pipelines);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($pipelines) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching pipelines page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($pipelines) . " pipelines from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch pipelines page {$page}: " . $e->getMessage());
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
                        'pipelines' => [],
                        'error' => 'Error fetching pipelines page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allPipelines) . ' pipelines in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allPipelines);
                $this->logRelationshipStats($relationshipStats, count($allPipelines), $command);
            }

            return [
                'success' => true,
                'pipelines' => $allPipelines
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in pipelines fetch process: " . $e->getMessage());
            }

            Log::error("Error in pipelines fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'pipelines' => [],
                'error' => 'Error in pipelines fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for pipelines
     *
     * @param array $pipelines
     * @return array
     */
    protected function calculateRelationshipStats(array $pipelines): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($pipelines as $pipeline) {
            if (isset($pipeline['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($pipeline['relationships'][$relationship]['data']['id']) ||
                        (isset($pipeline['relationships'][$relationship]['data']) && is_array($pipeline['relationships'][$relationship]['data']))
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
     * @param int $totalPipelines
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalPipelines, Command $command): void
    {
        if ($totalPipelines > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalPipelines) * 100, 2);
                $command->info("Pipelines with {$relationship} relationship: {$count} ({$percentage}%)");
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