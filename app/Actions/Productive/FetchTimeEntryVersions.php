<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;

class FetchTimeEntryVersions extends AbstractAction
{
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
            
            // Optional: Filter time entry versions by date range (e.g., last 90 days)
            $startDate = now()->subDays(90)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for time entry versions
                    $response = $client->get("time_entry_versions", [
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => '-created_at', // Newest first
                        'filter' => [
                            'created_at' => "{$startDate}T00:00:00Z...{$endDate}T23:59:59Z"
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

                    $timeEntryVersions = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $timeEntryVersions = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $timeEntryVersions,
                        'command' => $command
                    ]);

                    $allTimeEntryVersions = array_merge($allTimeEntryVersions, $timeEntryVersions);

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
                    if (count($timeEntryVersions) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching time entry versions page {$page}...");
                        }
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch time entry versions page {$page}: " . $e->getMessage());
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
                $command->info('Found ' . count($allTimeEntryVersions) . ' time entry versions in total');
            }

            return [
                'success' => true,
                'time_entry_versions' => $allTimeEntryVersions
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Failed to fetch time entry versions: ' . $e->getMessage());
            }
            return [
                'success' => false,
                'time_entry_versions' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}
