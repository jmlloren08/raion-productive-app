<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchAttachments extends AbstractAction
{
    /**
     * Define include relationships for attachments
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'creator',
        'invoice',
        'purchase_order',
        'bill',
        'email',
        'page',
        'expense',
        'comment',
        'document_style',
        'document_type',
        'deal',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['creator'],
        ['invoice'],
        ['purchase_order'],
        ['bill'],
        ['email'],
        ['page'],
        ['expense'],
        ['comment'],
        ['document_style'],
        ['document_type'],
        ['deal'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch attachments from the Productive API
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
                'attachments' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching attachments...');
            }

            $allAttachments = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for attachments
                    $response = $apiClient->get("attachments", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch attachments: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'attachments' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $attachments = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $attachments = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $attachments,
                        'command' => $command
                    ]);

                    $allAttachments = array_merge($allAttachments, $attachments);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($attachments) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching attachments page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($attachments) . " attachments from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch attachments page {$page}: " . $e->getMessage());
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
                        'attachments' => [],
                        'error' => 'Error fetching attachments page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allAttachments) . ' attachments in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allAttachments);
                $this->logRelationshipStats($relationshipStats, count($allAttachments), $command);
            }

            return [
                'success' => true,
                'attachments' => $allAttachments
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in attachments fetch process: " . $e->getMessage());
            }

            Log::error("Error in attachments fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'attachments' => [],
                'error' => 'Error in attachments fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for attachments
     *
     * @param array $attachments
     * @return array
     */
    protected function calculateRelationshipStats(array $attachments): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($attachments as $attachment) {
            if (isset($attachment['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($attachment['relationships'][$relationship]['data']['id']) ||
                        (isset($attachment['relationships'][$relationship]['data']) && is_array($attachment['relationships'][$relationship]['data']))
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
     * @param int $totalAttachments
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalAttachments, Command $command): void
    {
        if ($totalAttachments > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalAttachments) * 100, 2);
                $command->info("Attachments with {$relationship} relationship: {$count} ({$percentage}%)");
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