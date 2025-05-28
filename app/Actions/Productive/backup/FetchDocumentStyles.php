<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;

class FetchDocumentStyles extends AbstractAction
{
    /**
     * Define include relationships for document styles
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'attachments'
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['attachments'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch document styles from the Productive API
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
                'document_styles' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching document styles...');
            }
            $allDocumentStyles = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {                    // Following Productive API docs for document styles
                    $response = $client->get("document_styles", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ]
                    ])->throw();

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'document_styles' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }
                    $documentStyles = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $documentStyles = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $documentStyles,
                        'command' => $command
                    ]);

                    $allDocumentStyles = array_merge($allDocumentStyles, $documentStyles);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($documentStyles) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching document styles page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($documentStyles) . " document styles from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch document styles page {$page}: " . $e->getMessage());
                    }                    // If 'include' parameter is causing problems, try with fallback includes
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
                        'document_styles' => [],
                        'error' => 'Error fetching document styles page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allDocumentStyles) . ' document styles in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allDocumentStyles);
                $this->logRelationshipStats($relationshipStats, count($allDocumentStyles), $command);
            }

            return [
                'success' => true,
                'document_styles' => $allDocumentStyles
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in document styles fetch process: " . $e->getMessage());
            }
            return [
                'success' => false,
                'document_styles' => [],
                'error' => 'Error in document styles fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for document styles
     *
     * @param array $documentStyles
     * @return array
     */
    protected function calculateRelationshipStats(array $documentStyles): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($documentStyles as $documentStyle) {
            if (isset($documentStyle['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($documentStyle['relationships'][$relationship]['data']['id'])) {
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
     * @param int $totalDocumentStyles
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalDocumentStyles, Command $command): void
    {
        if ($totalDocumentStyles > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalDocumentStyles) * 100, 2);
                $command->info("Document styles with {$relationship} relationship: {$count} ({$percentage}%)");
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
