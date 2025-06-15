<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchTimeSheets extends AbstractAction
{
    /**
     * Define include relationships for time sheets
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'person',
        'creator',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['person'],
        ['creator'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch time sheets from the Productive API
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
                'timesheets' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching time sheets...');
            }

            $allTimeSheets = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("timesheets", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => '-date' // Newest first
                    ])->throw();

                    if(!$response->successful()) {
                        throw new \Exception("Failed to fetch time sheets: " . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'timesheets' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $timeSheets = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $timeSheets = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $timeSheets,
                        'command' => $command
                    ]);

                    $allTimeSheets = array_merge($allTimeSheets, $timeSheets);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($timeSheets) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching time sheets page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($timeSheets) . " time sheets from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch time sheets page {$page}: " . $e->getMessage());
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
                        'timesheets' => [],
                        'error' => 'Error fetching time sheets page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allTimeSheets) . ' time sheets in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allTimeSheets);
                $this->logRelationshipStats($relationshipStats, count($allTimeSheets), $command);
            }

            return [
                'success' => true,
                'timesheets' => $allTimeSheets
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in time sheets fetch process: " . $e->getMessage());
            }

            Log::error("Error in time sheets fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'timesheets' => [],
                'error' => 'Error in time sheets fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for time sheets
     *
     * @param array $timeSheets
     * @return array
     */
    protected function calculateRelationshipStats(array $timeSheets): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($timeSheets as $sheet) {
            if (isset($sheet['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($sheet['relationships'][$relationship]['data']['id'])) {
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
     * @param int $totalSheets
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalSheets, Command $command): void
    {
        if ($totalSheets > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalSheets) * 100, 2);
                $command->info("Time sheets with {$relationship} relationship: {$count} ({$percentage}%)");
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