<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchPages extends AbstractAction
{
    /**
     * Define include relationships for pages
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'creator',
        'project',
        'attachments',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['creator'],
        ['project'],
        ['attachments'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch pages from the Productive API
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
                'pages' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching pages...');
            }

            $allPages = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("pages", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch pages: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'pages' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $pages = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $pages = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $pages,
                        'command' => $command
                    ]);

                    $allPages = array_merge($allPages, $pages);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($pages) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching pages page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($pages) . " pages from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch pages page {$page}: " . $e->getMessage());
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
                        'pages' => [],
                        'error' => 'Error fetching pages page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allPages) . ' pages in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allPages);
                $this->logRelationshipStats($relationshipStats, count($allPages), $command);
            }

            return [
                'success' => true,
                'pages' => $allPages
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in pages fetch process: " . $e->getMessage());
            }

            Log::error("Error in pages fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'pages' => [],
                'error' => 'Error in pages fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for pages
     *
     * @param array $pages
     * @return array
     */
    protected function calculateRelationshipStats(array $pages): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($pages as $page) {
            if (isset($page['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($page['relationships'][$relationship]['data']['id']) ||
                        (isset($page['relationships'][$relationship]['data']) && is_array($page['relationships'][$relationship]['data']))
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
     * @param int $totalPages
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalPages, Command $command): void
    {
        if ($totalPages > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalPages) * 100, 2);
                $command->info("Pages with {$relationship} relationship: {$count} ({$percentage}%)");
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