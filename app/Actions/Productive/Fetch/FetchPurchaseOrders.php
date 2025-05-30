<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchPurchaseOrders extends AbstractAction
{
    /**
     * Define include relationships for purchase orders
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'deal',
        'creator',
        'document_type',
        'attachment',
        'bill_to',
        'bill_from',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['deal'],
        ['creator'],
        ['document_type'],
        ['attachment'],
        ['bill_to'],
        ['bill_from'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch purchase orders from the Productive API
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
                'purchase_orders' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching purchase orders...');
            }

            $allPurchaseOrders = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for purchase orders
                    $response = $apiClient->get("purchase_orders", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'created_at'
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch purchase orders: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'purchase_orders' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $purchaseOrders = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $purchaseOrders = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $purchaseOrders,
                        'command' => $command
                    ]);

                    $allPurchaseOrders = array_merge($allPurchaseOrders, $purchaseOrders);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($purchaseOrders) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching purchase orders page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($purchaseOrders) . " purchase orders from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch purchase orders page {$page}: " . $e->getMessage());
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
                        'purchase_orders' => [],
                        'error' => 'Error fetching purchase orders page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allPurchaseOrders) . ' purchase orders in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allPurchaseOrders);
                $this->logRelationshipStats($relationshipStats, count($allPurchaseOrders), $command);
            }

            return [
                'success' => true,
                'purchase_orders' => $allPurchaseOrders
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in purchase orders fetch process: " . $e->getMessage());
            }

            Log::error("Error in purchase orders fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'purchase_orders' => [],
                'error' => 'Error in purchase orders fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for purchase orders
     *
     * @param array $purchaseOrders
     * @return array
     */
    protected function calculateRelationshipStats(array $purchaseOrders): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($purchaseOrders as $purchaseOrder) {
            if (isset($purchaseOrder['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($purchaseOrder['relationships'][$relationship]['data']['id']) ||
                        (isset($purchaseOrder['relationships'][$relationship]['data']) && is_array($purchaseOrder['relationships'][$relationship]['data']))
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
     * @param int $totalPurchaseOrders
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalPurchaseOrders, Command $command): void
    {
        if ($totalPurchaseOrders > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalPurchaseOrders) * 100, 2);
                $command->info("Purchase Orders with {$relationship} relationship: {$count} ({$percentage}%)");
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