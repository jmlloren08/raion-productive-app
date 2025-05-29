<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchLostReasons extends AbstractAction
{
    /**
     * Define include relationships for lost reasons
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'company'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['company'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch lost reasons from the Productive API
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
                'lost_reasons' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching lost reasons...');
            }

            $allLostReasons = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("lost_reasons", [
                        // 'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch lost reasons: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'lost_reasons' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $lostReasons = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $lostReasons = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $lostReasons,
                        'command' => $command
                    ]);

                    $allLostReasons = array_merge($allLostReasons, $lostReasons);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($lostReasons) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching lost reasons page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($lostReasons) . " lost reasons from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch lost reasons page {$page}: " . $e->getMessage());
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
                        'lost_reasons' => [],
                        'error' => 'Error fetching lost reasons page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allLostReasons) . ' lost reasons in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allLostReasons);
                $this->logRelationshipStats($relationshipStats, count($allLostReasons), $command);
            }

            return [
                'success' => true,
                'lost_reasons' => $allLostReasons
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in lost reasons fetch process: " . $e->getMessage());
            }

            Log::error("Error in lost reasons fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'lost_reasons' => [],
                'error' => 'Error in lost reasons fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for lost reasons
     *
     * @param array $lostReasons
     * @return array
     */
    protected function calculateRelationshipStats(array $lostReasons): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($lostReasons as $lostReason) {
            if (isset($lostReason['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($lostReason['relationships'][$relationship]['data']['id']) ||
                        (isset($lostReason['relationships'][$relationship]['data']) && is_array($lostReason['relationships'][$relationship]['data']))
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
     * @param int $totalLostReasons
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalLostReasons, Command $command): void
    {
        if ($totalLostReasons > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalLostReasons) * 100, 2);
                $command->info("Lost reasons with {$relationship} relationship: {$count} ({$percentage}%)");
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
