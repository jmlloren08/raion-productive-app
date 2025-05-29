<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchProjects extends AbstractAction
{
    /**
     * Define include relationships for projects
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'company',
        'project_manager',
        'last_actor',
        'workflow'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['company'],
        ['project_manager'],
        ['last_actor'],
        ['workflow'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch projects from the Productive API
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
                'projects' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching projects...');
            }

            $allProjects = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for projects
                    $response = $apiClient->get("projects", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch projects: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'projects' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $projects = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $projects = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $projects,
                        'command' => $command
                    ]);

                    $allProjects = array_merge($allProjects, $projects);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($projects) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching projects page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($projects) . " projects from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch projects page {$page}: " . $e->getMessage());
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
                        'projects' => [],
                        'error' => 'Error fetching projects page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allProjects) . ' projects in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allProjects);
                $this->logRelationshipStats($relationshipStats, count($allProjects), $command);
            }

            return [
                'success' => true,
                'projects' => $allProjects
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in projects fetch process: " . $e->getMessage());
            }

            Log::error("Error in projects fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'projects' => [],
                'error' => 'Error in projects fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for projects
     *
     * @param array $projects
     * @return array
     */
    protected function calculateRelationshipStats(array $projects): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($projects as $project) {
            if (isset($project['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($project['relationships'][$relationship]['data']['id']) ||
                        (isset($project['relationships'][$relationship]['data']) && is_array($project['relationships'][$relationship]['data']))
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
     * @param int $totalProjects
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalProjects, Command $command): void
    {
        if ($totalProjects > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalProjects) * 100, 2);
                $command->info("Projects with {$relationship} relationship: {$count} ({$percentage}%)");
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
