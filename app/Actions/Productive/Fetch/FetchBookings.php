<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchBookings extends AbstractAction
{
    /**
     * Define include relationships for bookings
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'service',
        'event',
        'person',
        'creator',
        'updater',
        'approver',
        'rejecter',
        'canceler',
        'origin',
        'approval_statuses',
        'attachments'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['service'],
        ['event'],
        ['person'],
        ['creator'],
        ['updater'],
        ['approver'],
        ['rejecter'],
        ['canceler'],
        ['origin'],
        ['approval_statuses'],
        ['attachments'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch bookings from the Productive API
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
                'bookings' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching bookings...');
            }

            $allBookings = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for bookings
                    $response = $apiClient->get("bookings", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch bookings: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'bookings' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $bookings = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $bookings = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $bookings,
                        'command' => $command
                    ]);

                    $allBookings = array_merge($allBookings, $bookings);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($bookings) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching bookings page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($bookings) . " bookings from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch bookings page {$page}: " . $e->getMessage());
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
                        'bookings' => [],
                        'error' => 'Error fetching bookings page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allBookings) . ' bookings in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allBookings);
                $this->logRelationshipStats($relationshipStats, count($allBookings), $command);
            }

            return [
                'success' => true,
                'bookings' => $allBookings
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in bookings fetch process: " . $e->getMessage());
            }

            Log::error("Error in bookings fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'bookings' => [],
                'error' => 'Error in bookings fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for bookings
     *
     * @param array $bookings
     * @return array
     */
    protected function calculateRelationshipStats(array $bookings): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($bookings as $booking) {
            if (isset($booking['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($booking['relationships'][$relationship]['data']['id']) ||
                        (isset($booking['relationships'][$relationship]['data']) && is_array($booking['relationships'][$relationship]['data']))
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
     * @param int $totalBookings
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalBookings, Command $command): void
    {
        if ($totalBookings > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalBookings) * 100, 2);
                $command->info("Bookings with {$relationship} relationship: {$count} ({$percentage}%)");
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