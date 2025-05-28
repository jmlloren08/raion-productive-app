<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FetchTimeEntryVersions extends AbstractAction
{
    /**
     * Define include relationships for time entry versions
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'creator'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['creator'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch time entry versions from the Productive API
     *
     * @param array $parameters
     * @return array
     */
    public function handle(array $parameters = []): array
    {
        $client = $parameters['client'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$client) {
            return [
                'success' => false,
                'time_entry_versions' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching time entry versions...');
            }

            $allTimeEntryVersions = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for time entry versions
                    $response = $client->get("time_entry_versions", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => '-created_at' // Newest first
                    ])->throw();

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'time_entry_versions' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $timeEntryVersions = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $timeEntryVersions = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $timeEntryVersions,
                        'command' => $command
                    ]);

                    $allTimeEntryVersions = array_merge($allTimeEntryVersions, $timeEntryVersions);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($timeEntryVersions) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching time entry versions page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($timeEntryVersions) . " versions from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch time entry versions page {$page}: " . $e->getMessage());
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
                        'time_entry_versions' => [],
                        'error' => 'Error fetching time entry versions page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allTimeEntryVersions) . ' time entry versions in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allTimeEntryVersions);
                $this->logRelationshipStats($relationshipStats, count($allTimeEntryVersions), $command);
            }

            return [
                'success' => true,
                'time_entry_versions' => $allTimeEntryVersions
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in time entry versions fetch process: " . $e->getMessage());
            }

            return [
                'success' => false,
                'time_entry_versions' => [],
                'error' => 'Error in time entry versions fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for versions
     *
     * @param array $versions
     * @return array
     */
    protected function calculateRelationshipStats(array $versions): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($versions as $version) {
            if (isset($version['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($version['relationships'][$relationship]['data']['id'])) {
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
     * @param int $totalVersions
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalVersions, Command $command): void
    {
        if ($totalVersions > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalVersions) * 100, 2);
                $command->info("Versions with {$relationship} relationship: {$count} ({$percentage}%)");
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
