<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchDocumentTypes extends AbstractAction
{
    /**
     * Define include relationships for document types
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'subsidiary',
        'document_style',
        'attachments',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['subsidiary'],
        ['document_style'],
        ['attachments'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch document types from the Productive API
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
                'document_types' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching document types...');
            }

            $allDocumentTypes = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for document types
                    $response = $apiClient->get("document_types", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch document types: ' . $response->body());
                    }

                    $responseBody = $response->json();
                    
                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'document_types' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $documentTypes = $responseBody['data'];
                    
                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $documentTypes = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $documentTypes,
                        'command' => $command
                    ]);

                    $allDocumentTypes = array_merge($allDocumentTypes, $documentTypes);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($documentTypes) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching document types page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($documentTypes) . " document types from page " . ($page - 1));
                    }

                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch document types page {$page}: " . $e->getMessage());
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
                        'document_types' => [],
                        'error' => 'Error fetching document types page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allDocumentTypes) . ' document types in total');
                
                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allDocumentTypes);
                $this->logRelationshipStats($relationshipStats, count($allDocumentTypes), $command);
            }

            return [
                'success' => true,
                'document_types' => $allDocumentTypes
            ];

        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in document types fetch process: " . $e->getMessage());
            }

            Log::error("Error in document types fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'document_types' => [],
                'error' => 'Error in document types fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for document types
     *
     * @param array $documentTypes
     * @return array
     */
    protected function calculateRelationshipStats(array $documentTypes): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($documentTypes as $documentType) {
            if (isset($documentType['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($documentType['relationships'][$relationship]['data']['id']) ||
                        (isset($documentType['relationships'][$relationship]['data']) && is_array($documentType['relationships'][$relationship]['data']))
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
     * @param int $totalDocumentTypes
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalDocumentTypes, Command $command): void
    {
        if ($totalDocumentTypes > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalDocumentTypes) * 100, 2);
                $command->info("Document types with {$relationship} relationship: {$count} ({$percentage}%)");
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
