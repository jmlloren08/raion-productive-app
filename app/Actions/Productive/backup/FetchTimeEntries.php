<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FetchTimeEntries extends AbstractAction
{
    /**
     * Define include relationships for time entries
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'person',
        'service',
        'task',
        'approver',
        'updater',
        'rejecter',
        'creator',
        'last_actor',
        'person_subsidiary',
        'deal_subsidiary',
        'timesheet'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['person'],
        ['service'],
        ['task'],
        ['approver'],
        ['updater'],
        ['rejecter'],
        ['creator'],
        ['last_actor'],
        ['person_subsidiary'],
        ['deal_subsidiary'],
        ['timesheet'],
        []  // Empty array means no includes
    ];


    /**
     * Fetch time entries from the Productive API
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
                'time_entries' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching time entries...');
            }

            $allTimeEntries = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for time entries
                    $response = $client->get("time_entries", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => '-date' // Newest first
                    ])->throw();

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'time_entries' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $timeEntries = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $timeEntries = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $timeEntries,
                        'command' => $command
                    ]);

                    $allTimeEntries = array_merge($allTimeEntries, $timeEntries);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($timeEntries) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching time entries page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($timeEntries) . " time entries from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch time entries page {$page}: " . $e->getMessage());
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
                        'time_entries' => [],
                        'error' => 'Error fetching time entries page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allTimeEntries) . ' time entries in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allTimeEntries);
                $this->logRelationshipStats($relationshipStats, count($allTimeEntries), $command);
            }

            return [
                'success' => true,
                'time_entries' => $allTimeEntries
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in time entries fetch process: " . $e->getMessage());
            }

            return [
                'success' => false,
                'time_entries' => [],
                'error' => 'Error in time entries fetch process: ' . $e->getMessage()
            ];
        }
    }
    /**
     * Calculate relationship statistics for time entries
     *
     * @param array $timeEntries
     * @return array
     */
    protected function calculateRelationshipStats(array $timeEntries): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($timeEntries as $entry) {
            if (isset($entry['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($entry['relationships'][$relationship]['data']['id'])) {
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
                $command->info("Time entries with {$relationship} relationship: {$count} ({$percentage}%)");
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
