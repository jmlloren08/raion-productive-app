<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchActivities extends AbstractAction
{
    /**
     * Define include relationships for activities
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'creator',
        'comment',
        'email',
        'attachment',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['creator'],
        ['comment'],
        ['email'],
        ['attachment'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch activities from the Productive API
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
                'activities' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching activities...');
            }

            $allActivities = [];
            $page = 1;
            $pageSize = 50; // Reduced page size
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);
            $totalProcessed = 0;

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for activities
                    $response = $apiClient->get("activities", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch activities: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'activities' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $activities = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $activities = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $activities,
                        'command' => $command
                    ]);

                    // Process activities in smaller chunks
                    $chunkSize = 10;
                    $chunks = array_chunk($activities, $chunkSize);
                    
                    foreach ($chunks as $chunk) {
                        // Add chunk to the result array
                        $allActivities = array_merge($allActivities, $chunk);
                        $totalProcessed += count($chunk);
                        
                        // Free up memory after each chunk
                        unset($chunk);
                        gc_collect_cycles();
                    }

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($activities) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching activities page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($activities) . " activities from page " . ($page - 1));
                    }

                    // Free up memory
                    unset($responseBody);
                    unset($activities);
                    gc_collect_cycles();

                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch activities page {$page}: " . $e->getMessage());
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
                        'activities' => [],
                        'error' => 'Error fetching activities page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . $totalProcessed . ' activities in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allActivities);
                $this->logRelationshipStats($relationshipStats, $totalProcessed, $command);
            }

            return [
                'success' => true,
                'activities' => $allActivities
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in activities fetch process: " . $e->getMessage());
            }

            Log::error("Error in activities fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'activities' => [],
                'error' => 'Error in activities fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for activities
     *
     * @param array $activities
     * @return array
     */
    protected function calculateRelationshipStats(array $activities): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($activities as $activity) {
            if (isset($activity['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($activity['relationships'][$relationship]['data']['id']) ||
                        (isset($activity['relationships'][$relationship]['data']) && is_array($activity['relationships'][$relationship]['data']))
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
     * @param int $totalActivities
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalActivities, Command $command): void
    {
        if ($totalActivities > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalActivities) * 100, 2);
                $command->info("Activities with {$relationship} relationship: {$count} ({$percentage}%)");
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