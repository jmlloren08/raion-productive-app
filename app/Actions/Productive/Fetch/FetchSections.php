<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchSections extends AbstractAction
{
    /**
     * Define include relationships for sections
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'deal',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['deal'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch sections from the Productive API
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
                'sections' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching sections...');
            }

            $allSections = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("sections", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch sections: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'sections' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $sections = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $sections = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $sections,
                        'command' => $command
                    ]);

                    $allSections = array_merge($allSections, $sections);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($sections) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching sections page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($sections) . " sections from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch sections page {$page}: " . $e->getMessage());
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
                        'sections' => [],
                        'error' => 'Error fetching sections page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allSections) . ' sections in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allSections);
                $this->logRelationshipStats($relationshipStats, count($allSections), $command);
            }

            return [
                'success' => true,
                'sections' => $allSections
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in sections fetch process: " . $e->getMessage());
            }

            Log::error("Error in sections fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'sections' => [],
                'error' => 'Error in sections fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for sections
     *
     * @param array $sections
     * @return array
     */
    protected function calculateRelationshipStats(array $sections): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($sections as $section) {
            if (isset($section['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($section['relationships'][$relationship]['data']['id']) ||
                        (isset($section['relationships'][$relationship]['data']) && is_array($section['relationships'][$relationship]['data']))
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
     * @param int $totalSections
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalSections, Command $command): void
    {
        if ($totalSections > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalSections) * 100, 2);
                $command->info("Sections with {$relationship} relationship: {$count} ({$percentage}%)");
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