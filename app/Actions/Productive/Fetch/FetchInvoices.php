<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchInvoices extends AbstractAction
{
    /**
     * Define include relationships for invoices
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'company',
        'creator',
        'deal',
        'contact_entry',
        'subsidiary',
        'tax_rate',
        'document_type',
        'document_style',
        'attachment'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['company'],
        ['creator'],
        ['deal'],
        ['contact_entry'],
        ['subsidiary'],
        ['tax_rate'],
        ['document_type'],
        ['document_style'],
        ['attachment'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch invoices from the Productive API
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
                'invoices' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching invoices...');
            }

            $allInvoices = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for invoices
                    $response = $apiClient->get("invoices", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'created_at'
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch invoices: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'invoices' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $invoices = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $invoices = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $invoices,
                        'command' => $command
                    ]);

                    $allInvoices = array_merge($allInvoices, $invoices);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($invoices) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching invoices page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($invoices) . " invoices from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch invoices page {$page}: " . $e->getMessage());
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
                        'invoices' => [],
                        'error' => 'Error fetching invoices page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allInvoices) . ' invoices in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allInvoices);
                $this->logRelationshipStats($relationshipStats, count($allInvoices), $command);
            }

            return [
                'success' => true,
                'invoices' => $allInvoices
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in invoices fetch process: " . $e->getMessage());
            }

            Log::error("Error in invoices fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'invoices' => [],
                'error' => 'Error in invoices fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for invoices
     *
     * @param array $invoices
     * @return array
     */
    protected function calculateRelationshipStats(array $invoices): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($invoices as $invoice) {
            if (isset($invoice['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($invoice['relationships'][$relationship]['data']['id']) ||
                        (isset($invoice['relationships'][$relationship]['data']) && is_array($invoice['relationships'][$relationship]['data']))
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
     * @param int $totalInvoices
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalInvoices, Command $command): void
    {
        if ($totalInvoices > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalInvoices) * 100, 2);
                $command->info("Invoices with {$relationship} relationship: {$count} ({$percentage}%)");
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