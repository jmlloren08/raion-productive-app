<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;

class FetchProjects extends AbstractAction
{
    /**
     * Fetch projects from the Productive API
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
                'projects' => [],
                'error' => 'Client not provided'
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
            $includeParam = 'company'; // Include company relationships

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for projects
                    $response = $client->get("projects", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ])->throw();

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        continue; // Skip this page and try the next one
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
                    if (count($projects) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching projects page {$page}...");
                        }
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch projects page {$page}: " . $e->getMessage());
                    }

                    // If 'include' parameter is causing problems, try without it
                    if (strpos($e->getMessage(), 'include') !== false && $includeParam !== '') {
                        if ($command instanceof Command) {
                            $command->warn("Retrying without include parameter");
                        }
                        $includeParam = '';
                        continue; // Retry the current page without include parameter
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
                $command->info('Found ' . count($allProjects) . ' projects in total');

                // Count projects with company relationships
                $projectsWithCompany = 0;
                $missingCompanyInfo = [];
                foreach ($allProjects as $project) {
                    if (isset($project['relationships']['company']['data']['id'])) {
                        $projectsWithCompany++;
                    } else {
                        // Record projects missing company info (up to 5 for logging)
                        if (count($missingCompanyInfo) < 5) {
                            $missingCompanyInfo[] = [
                                'id' => $project['id'] ?? 'unknown',
                                'name' => $project['attributes']['name'] ?? 'unnamed'
                            ];
                        }
                    }
                }

                $command->info("Projects with company relationship: {$projectsWithCompany} of " . count($allProjects) .
                    " (" . round(($projectsWithCompany / max(1, count($allProjects))) * 100, 2) . "%)");

                if (!empty($missingCompanyInfo)) {
                    $command->warn("Examples of projects missing company relationship: " . json_encode($missingCompanyInfo));
                }
            }

            return [
                'success' => true,
                'projects' => $allProjects
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Failed to fetch projects: ' . $e->getMessage());
                if ($e instanceof \Illuminate\Http\Client\RequestException) {
                    $command->error('Response: ' . $e->response->body());
                }
            }
            return [
                'success' => false,
                'projects' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}
