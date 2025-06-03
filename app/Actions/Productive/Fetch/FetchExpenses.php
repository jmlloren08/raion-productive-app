<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchExpenses extends AbstractAction
{
    /**
     * Define include relationships for expenses
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'deal',
        'service_type',
        'person',
        'creator',
        'rejecter',
        'approver',
        'service',
        'purchase_order',
        'tax_rate',
        'attachment',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['deal'],
        ['service_type'],
        ['person'],
        ['creator'],
        ['rejecter'],
        ['approver'],
        ['service'],
        ['purchase_order'],
        ['tax_rate'],
        ['attachment'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch expenses from the Productive API
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
                'expenses' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching expenses...');
            }

            $allExpenses = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for expenses
                    $response = $apiClient->get("expenses", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch expenses: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'expenses' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $expenses = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $expenses = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $expenses,
                        'command' => $command
                    ]);

                    $allExpenses = array_merge($allExpenses, $expenses);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($expenses) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching expenses page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($expenses) . " expenses from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch expenses page {$page}: " . $e->getMessage());
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
                        'expenses' => [],
                        'error' => 'Error fetching expenses page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allExpenses) . ' expenses in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allExpenses);
                $this->logRelationshipStats($relationshipStats, count($allExpenses), $command);
            }

            return [
                'success' => true,
                'expenses' => $allExpenses
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in expenses fetch process: " . $e->getMessage());
            }

            Log::error("Error in expenses fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'expenses' => [],
                'error' => 'Error in expenses fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for expenses
     *
     * @param array $expenses
     * @return array
     */
    protected function calculateRelationshipStats(array $expenses): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($expenses as $expense) {
            if (isset($expense['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($expense['relationships'][$relationship]['data']['id']) ||
                        (isset($expense['relationships'][$relationship]['data']) && is_array($expense['relationships'][$relationship]['data']))
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
     * @param int $totalExpenses
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalExpenses, Command $command): void
    {
        if ($totalExpenses > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalExpenses) * 100, 2);
                $command->info("Expenses with {$relationship} relationship: {$count} ({$percentage}%)");
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