<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchTags extends AbstractAction
{
    /**
     * Define include relationships for tags
     * 
     * @var array
     */
    protected array $includeRelationships = [
        // No relationships by default
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
     * Fetch tags from the Productive API
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
                'tags' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching tags...');
            }

            $allTags = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("tags", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ]
                    ])->throw();

                    if(!$response->successful()) {
                        throw new \Exception("Failed to fetch tags: " . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'tags' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $tags = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $tags = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $tags,
                        'command' => $command
                    ]);

                    $allTags = array_merge($allTags, $tags);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($tags) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching tags page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($tags) . " tags from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch tags page {$page}: " . $e->getMessage());
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
                        'tags' => [],
                        'error' => 'Error fetching tags page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allTags) . ' tags in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allTags);
                $this->logRelationshipStats($relationshipStats, count($allTags), $command);
            }

            return [
                'success' => true,
                'tags' => $allTags
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in tags fetch process: " . $e->getMessage());
            }

            Log::error("Error in tags fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'tags' => [],
                'error' => 'Error in tags fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for tags
     *
     * @param array $tags
     * @return array
     */
    protected function calculateRelationshipStats(array $tags): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($tags as $tag) {
            if (isset($tag['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($tag['relationships'][$relationship]['data']['id'])) {
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
                $command->info("Tags with {$relationship} relationship: {$count} ({$percentage}%)");
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