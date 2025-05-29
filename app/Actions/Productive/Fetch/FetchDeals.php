<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchDeals extends AbstractAction
{
    /**
     * Define include relationships for deals
     * 
     * @var array
     */    
    protected array $includeRelationships = [
        'creator',
        'company',
        'document_type',
        'responsible',
        'deal_status',
        'project',
        'lost_reason',
        'contract',
        'contact',
        'subsidiary',
        'template',
        'tax_rate',
        'origin_deal',
        'approval_policy_assignment',
        'next_todo'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['creator'],
        ['company'],
        ['document_type'],
        ['responsible'],
        ['deal_status'],
        ['project'],
        ['lost_reason'],
        ['contract'],
        ['contact'],
        ['subsidiary'],
        ['template'],
        ['tax_rate'],
        ['origin_deal'],
        ['approval_policy_assignment'],
        ['next_todo'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch deals from the Productive API
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
                'deals' => [],
                'error' => 'API client is required'
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
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for deals
                    $response = $apiClient->get("deals", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch deals: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'deals' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
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

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
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

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($deals) . " deals from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch deals page {$page}: " . $e->getMessage());
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
                        'deals' => [],
                        'error' => 'Error fetching deals page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allDeals) . ' deals in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allDeals);
                $this->logRelationshipStats($relationshipStats, count($allDeals), $command);
            }

            return [
                'success' => true,
                'deals' => $allDeals
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in deals fetch process: " . $e->getMessage());
            }

            Log::error("Error in deals fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'deals' => [],
                'error' => 'Error in deals fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for deals
     *
     * @param array $deals
     * @return array
     */
    protected function calculateRelationshipStats(array $deals): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($deals as $deal) {
            if (isset($deal['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($deal['relationships'][$relationship]['data']['id']) ||
                        (isset($deal['relationships'][$relationship]['data']) && is_array($deal['relationships'][$relationship]['data']))
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
     * @param int $totalDeals
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalDeals, Command $command): void
    {
        if ($totalDeals > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalDeals) * 100, 2);
                $command->info("Deals with {$relationship} relationship: {$count} ({$percentage}%)");
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
