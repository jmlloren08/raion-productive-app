<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchInvoiceAttributions extends AbstractAction
{
    /**
     * Define include relationships for invoice attributions
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'invoice',
        'budget',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['invoice'], 
        ['budget'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch invoice attributions from the Productive API
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
                'invoice_attributions' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching invoice attributions...');
            }

            $allInvoiceAttributions = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for invoice attributions
                    $response = $apiClient->get("invoice_attributions", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch invoice attributions: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'invoice_attributions' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $invoiceAttributions = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $invoiceAttributions = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $invoiceAttributions,
                        'command' => $command
                    ]);

                    $allInvoiceAttributions = array_merge($allInvoiceAttributions, $invoiceAttributions);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($invoiceAttributions) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching invoice attributions page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($invoiceAttributions) . " invoice attributions from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch invoice attributions page {$page}: " . $e->getMessage());
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
                        'invoice_attributions' => [],
                        'error' => 'Error fetching invoice attributions page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allInvoiceAttributions) . ' invoice attributions in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allInvoiceAttributions);
                $this->logRelationshipStats($relationshipStats, count($allInvoiceAttributions), $command);
            }

            return [
                'success' => true,
                'invoice_attributions' => $allInvoiceAttributions
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in invoice attributions fetch process: " . $e->getMessage());
            }

            Log::error("Error in invoice attributions fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'invoice_attributions' => [],
                'error' => 'Error in invoice attributions fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for invoice attributions
     *
     * @param array $invoiceAttributions
     * @return array
     */
    protected function calculateRelationshipStats(array $invoiceAttributions): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($invoiceAttributions as $invoiceAttribution) {
            if (isset($invoiceAttribution['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($invoiceAttribution['relationships'][$relationship]['data']['id']) ||
                        (isset($invoiceAttribution['relationships'][$relationship]['data']) && is_array($invoiceAttribution['relationships'][$relationship]['data']))
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
     * @param int $totalInvoiceAttributions
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalInvoiceAttributions, Command $command): void
    {
        if ($totalInvoiceAttributions > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalInvoiceAttributions) * 100, 2);
                $command->info("Invoice Attributions with {$relationship} relationship: {$count} ({$percentage}%)");
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