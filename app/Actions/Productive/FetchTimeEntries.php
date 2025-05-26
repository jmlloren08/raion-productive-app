<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;

class FetchTimeEntries extends AbstractAction
{
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
            $includeParam = 'task,service,person'; // Include relevant relationships
            
            // Optional: Filter time entries by date range (e.g., last 90 days)
            $startDate = now()->subDays(90)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for time entries
                    $response = $client->get("time_entries", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => '-date', // Newest first
                        'filter' => [
                            'date' => "{$startDate}...{$endDate}"
                        ]
                    ])->throw();

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        continue; // Skip this page and try the next one
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

                    // If 'included' data is present, log it for debugging
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $includedTypes = [];
                        foreach ($responseBody['included'] as $included) {
                            $type = $included['type'] ?? 'unknown';
                            $includedTypes[$type] = ($includedTypes[$type] ?? 0) + 1;
                        }
                        $command->info("Page {$page} included data: " . json_encode($includedTypes));
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
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch time entries page {$page}: " . $e->getMessage());
                    }

                    // If 'include' parameter is causing problems, try with fewer includes
                    if (strpos($e->getMessage(), 'include') !== false) {
                        if ($includeParam === 'task,service,person') {
                            if ($command instanceof Command) {
                                $command->warn("Retrying with only 'task,service' include parameter");
                            }
                            $includeParam = 'task,service';
                            continue; // Retry with only task and service
                        } else if ($includeParam === 'task,service') {
                            if ($command instanceof Command) {
                                $command->warn("Retrying with only 'task' include parameter");
                            }
                            $includeParam = 'task';
                            continue; // Retry with only task
                        } else if ($includeParam === 'task') {
                            if ($command instanceof Command) {
                                $command->warn("Retrying without include parameters");
                            }
                            $includeParam = '';
                            continue; // Retry without includes
                        }
                    }

                    if ($page > 1) {
                        // We already have some data, so we can continue with what we have
                        if ($command instanceof Command) {
                            $command->warn("Proceeding with partial data ({$page} pages fetched)");
                        }
                        $hasMorePages = false;
                    } else {
                        // First page failed, cannot continue
                        throw $e;
                    }
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allTimeEntries) . ' time entries in total');

                // Count time entries with various relationships
                $entriesWithTask = 0;
                $entriesWithService = 0;
                $entriesWithPerson = 0;

                foreach ($allTimeEntries as $entry) {
                    if (isset($entry['relationships']['task']['data']['id'])) $entriesWithTask++;
                    if (isset($entry['relationships']['service']['data']['id'])) $entriesWithService++;
                    if (isset($entry['relationships']['person']['data']['id'])) $entriesWithPerson++;
                }

                $totalEntries = count($allTimeEntries);
                if ($totalEntries > 0) {
                    $command->info("Time entries with task relationship: {$entriesWithTask} of {$totalEntries} (" .
                        round(($entriesWithTask / $totalEntries) * 100, 2) . "%)");
                    $command->info("Time entries with service relationship: {$entriesWithService} of {$totalEntries} (" .
                        round(($entriesWithService / $totalEntries) * 100, 2) . "%)");
                    $command->info("Time entries with person relationship: {$entriesWithPerson} of {$totalEntries} (" .
                        round(($entriesWithPerson / $totalEntries) * 100, 2) . "%)");
                }
            }

            return [
                'success' => true,
                'time_entries' => $allTimeEntries
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Failed to fetch time entries: ' . $e->getMessage());
                if ($e instanceof \Illuminate\Http\Client\RequestException) {
                    $command->error('Response: ' . $e->response->body());
                }
            }
            return [
                'success' => false,
                'time_entries' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}
