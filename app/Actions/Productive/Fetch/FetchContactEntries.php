<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchContactEntries extends AbstractAction
{
    /**
     * Define include relationships for contact entries
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'company',
        'person',
        'subsidiary',   
        'purchase_order',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['company'],
        ['person'],
        ['subsidiary'],
        ['purchase_order'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch contact entries from the Productive API
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
                'contact_entries' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching contact entries...');
            }

            $allContactEntries = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("contact_entries", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch contact entries: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'contact_entries' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $contactEntries = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $contactEntries = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $contactEntries,
                        'command' => $command
                    ]);

                    $allContactEntries = array_merge($allContactEntries, $contactEntries);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($contactEntries) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching contact entries page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($contactEntries) . " contact entries from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch contact entries page {$page}: " . $e->getMessage());
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
                        'contact_entries' => [],
                        'error' => 'Error fetching contact entries page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allContactEntries) . ' contact entries in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allContactEntries);
                $this->logRelationshipStats($relationshipStats, count($allContactEntries), $command);
            }

            return [
                'success' => true,
                'contact_entries' => $allContactEntries
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in contact entries fetch process: " . $e->getMessage());
            }

            Log::error("Error in contact entries fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'contact_entries' => [],
                'error' => 'Error in contact entries fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for contact entries
     *
     * @param array $contactEntries
     * @return array
     */
    protected function calculateRelationshipStats(array $contactEntries): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($contactEntries as $contactEntry) {
            if (isset($contactEntry['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($contactEntry['relationships'][$relationship]['data']['id']) ||
                        (isset($contactEntry['relationships'][$relationship]['data']) && is_array($contactEntry['relationships'][$relationship]['data']))
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
     * @param int $totalContactEntries
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalContactEntries, Command $command): void
    {
        if ($totalContactEntries > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalContactEntries) * 100, 2);
                $command->info("Contact entries with {$relationship} relationship: {$count} ({$percentage}%)");
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
