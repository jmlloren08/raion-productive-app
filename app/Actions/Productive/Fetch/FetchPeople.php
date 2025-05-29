<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchPeople extends AbstractAction
{
    /**
     * Define include relationships for people
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'manager',
        'company',
        'subsidiary',
        'approval_policy_assignment',
        'teams'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['manager'],
        ['company'],
        ['subsidiary'],
        ['approval_policy_assignment'],
        ['teams'],
        [] // Empty array means no includes
    ];

    /**
     * Fetch people from the Productive API
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
                'people' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching people from Productive API...');
            }

            $allPeople = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for people
                    $response = $apiClient->get('/people', [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ]);

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch people: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'people' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $people = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $people = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $people,
                        'command' => $command
                    ]);

                    $allPeople = array_merge($allPeople, $people);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($people) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching people page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($people) . " people from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch people page {$page}: " . $e->getMessage());
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
                        'people' => [],
                        'error' => 'Error fetching people page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allPeople) . ' people in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allPeople);
                $this->logRelationshipStats($relationshipStats, count($allPeople), $command);
            }

            return [
                'success' => true,
                'people' => $allPeople
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in people fetch process: " . $e->getMessage());
            }

            Log::error("Error in people fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'people' => [],
                'error' => 'Error in people fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for people
     *
     * @param array $people
     * @return array
     */
    protected function calculateRelationshipStats(array $people): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($people as $person) {
            if (isset($person['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($person['relationships'][$relationship]['data']['id']) ||
                        (isset($person['relationships'][$relationship]['data']) && is_array($person['relationships'][$relationship]['data']))
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
     * @param int $totalPeople
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalPeople, Command $command): void
    {
        if ($totalPeople > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalPeople) * 100, 2);
                $command->info("People with {$relationship} relationship: {$count} ({$percentage}%)");
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
