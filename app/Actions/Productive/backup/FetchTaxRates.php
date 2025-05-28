<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchTaxRates extends AbstractAction
{
    /**
     * List of relationship types to include in the API request
     */
    protected array $includeRelationships = [
        'subsidiary'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['subsidiary'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch tax rates from the Productive API
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
                'tax_rates' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching tax rates...');
            }

            $allTaxRates = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $client->get("tax_rates", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name',
                    ])->throw();

                    $responseBody = $response->json();
                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        continue; // Skip this page and try the next one
                    }

                    $taxRates = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $taxRates = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $taxRates,
                        'command' => $command
                    ]);

                    $allTaxRates = array_merge($allTaxRates, $taxRates);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($taxRates) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching tax rates page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($taxRates) . " tax rates from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch tax rates page {$page}: " . $e->getMessage());
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
                        'tax_rates' => [],
                        'error' => 'Error fetching tax rates page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allTaxRates) . ' tax rates in total');

                // Calculate relationship statistics
                $stats = $this->calculateRelationshipStats($allTaxRates);
                $this->logRelationshipStats($stats, count($allTaxRates), $command);
            }

            return [
                'success' => true,
                'tax_rates' => $allTaxRates
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Failed to fetch tax rates: ' . $e->getMessage());
                if ($e instanceof \Illuminate\Http\Client\RequestException) {
                    $command->error('Response: ' . $e->response->body());
                }
            }
            return [
                'success' => false,
                'tax_rates' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate statistics about relationships in the tax rates
     */
    protected function calculateRelationshipStats(array $taxRates): array
    {
        $stats = [];
        foreach ($this->includeRelationships as $relationship) {
            $stats[$relationship] = 0;
        }

        foreach ($taxRates as $taxRate) {
            if (isset($taxRate['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($taxRate['relationships'][$relationship]['data']['id'])) {
                        $stats[$relationship]++;
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Log statistics about relationships in the tax rates
     */
    protected function logRelationshipStats(array $stats, int $totalTaxRates, Command $command): void
    {
        if ($totalTaxRates > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalTaxRates) * 100, 2);
                $command->info("Tax rates with {$relationship} relationship: {$count} ({$percentage}%)");
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
