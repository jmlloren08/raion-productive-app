<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;

class FetchDeals extends AbstractAction
{
    /**
     * Fetch deals from the Productive API
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
                'deals' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching deals...');
            }

            $allDeals = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = 'company,project'; // Include both company and project relationships

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for deals
                    $response = $client->get("deals", [
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

                    $deals = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $deals = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $deals,
                        'command' => $command
                    ]);

                    $allDeals = array_merge($allDeals, $deals);

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
                    if (count($deals) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching deals page {$page}...");
                        }
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch deals page {$page}: " . $e->getMessage());
                    }

                    // If 'include' parameter is causing problems, try with fewer includes
                    if (strpos($e->getMessage(), 'include') !== false) {
                        if ($includeParam === 'company,project') {
                            if ($command instanceof Command) {
                                $command->warn("Retrying with only 'company' include parameter");
                            }
                            $includeParam = 'company';
                            continue; // Retry the current page with only company
                        } else if ($includeParam === 'company') {
                            if ($command instanceof Command) {
                                $command->warn("Retrying with only 'project' include parameter");
                            }
                            $includeParam = 'project';
                            continue; // Retry with only project
                        } else if ($includeParam === 'project') {
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
                $command->info('Found ' . count($allDeals) . ' deals in total');

                // Count deals with company and project relationships
                $dealsWithCompany = 0;
                $dealsWithProject = 0;
                $dealsWithBoth = 0;

                foreach ($allDeals as $deal) {
                    $hasCompany = isset($deal['relationships']['company']['data']['id']);
                    $hasProject = isset($deal['relationships']['project']['data']['id']);

                    if ($hasCompany) $dealsWithCompany++;
                    if ($hasProject) $dealsWithProject++;
                    if ($hasCompany && $hasProject) $dealsWithBoth++;
                }

                $totalDeals = count($allDeals);
                if ($totalDeals > 0) {
                    $command->info("Deals with company relationship: {$dealsWithCompany} of {$totalDeals} (" .
                        round(($dealsWithCompany / $totalDeals) * 100, 2) . "%)");
                    $command->info("Deals with project relationship: {$dealsWithProject} of {$totalDeals} (" .
                        round(($dealsWithProject / $totalDeals) * 100, 2) . "%)");
                    $command->info("Deals with both company and project: {$dealsWithBoth} of {$totalDeals} (" .
                        round(($dealsWithBoth / $totalDeals) * 100, 2) . "%)");
                }
            }

            return [
                'success' => true,
                'deals' => $allDeals
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Failed to fetch deals: ' . $e->getMessage());
                if ($e instanceof \Illuminate\Http\Client\RequestException) {
                    $command->error('Response: ' . $e->response->body());
                }
            }
            return [
                'success' => false,
                'deals' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}
