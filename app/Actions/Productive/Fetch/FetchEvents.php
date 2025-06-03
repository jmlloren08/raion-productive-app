<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchEvents extends AbstractAction
{
    /**
     * Define include relationships for events
     * 
     * @var array
     */
    protected array $includeRelationships = [
        // No include relationships by default
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
     * Fetch events from the Productive API
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
                'events' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching events...');
            }

            $allEvents = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for events
                    $response = $apiClient->get("events", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch events: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'events' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $events = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $events = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $events,
                        'command' => $command
                    ]);

                    $allEvents = array_merge($allEvents, $events);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($events) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching events page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($events) . " events from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch events page {$page}: " . $e->getMessage());
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
                        'events' => [],
                        'error' => 'Error fetching events page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allEvents) . ' events in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allEvents);
                $this->logRelationshipStats($relationshipStats, count($allEvents), $command);
            }

            return [
                'success' => true,
                'events' => $allEvents
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in events fetch process: " . $e->getMessage());
            }

            Log::error("Error in events fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'events' => [],
                'error' => 'Error in events fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for events
     *
     * @param array $events
     * @return array
     */
    protected function calculateRelationshipStats(array $events): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($events as $event) {
            if (isset($event['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($event['relationships'][$relationship]['data']['id']) ||
                        (isset($event['relationships'][$relationship]['data']) && is_array($event['relationships'][$relationship]['data']))
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
     * @param int $totalEvents
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalEvents, Command $command): void
    {
        if ($totalEvents > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalEvents) * 100, 2);
                $command->info("Events with {$relationship} relationship: {$count} ({$percentage}%)");
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