<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchComments extends AbstractAction
{
    /**
     * Define include relationships for comments
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'company',
        'creator',
        'deal',
        'discussion',
        'invoice',
        'person',
        'pinned_by',
        'task',
        'purchase_order',
        'attachments',
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
        ['discussion'],
        ['invoice'],
        ['person'],
        ['pinned_by'],
        ['task'],
        ['purchase_order'],
        ['attachments'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch comments from the Productive API
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
                'comments' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching comments...');
            }

            $allComments = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for comments
                    $response = $apiClient->get("comments", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch comments: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'comments' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $comments = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $comments = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $comments,
                        'command' => $command
                    ]);

                    $allComments = array_merge($allComments, $comments);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($comments) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching comments page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($comments) . " comments from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch comments page {$page}: " . $e->getMessage());
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
                        'comments' => [],
                        'error' => 'Error fetching comments page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allComments) . ' comments in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allComments);
                $this->logRelationshipStats($relationshipStats, count($allComments), $command);
            }

            return [
                'success' => true,
                'comments' => $allComments
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in comments fetch process: " . $e->getMessage());
            }

            Log::error("Error in comments fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'comments' => [],
                'error' => 'Error in comments fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for comments
     *
     * @param array $comments
     * @return array
     */
    protected function calculateRelationshipStats(array $comments): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($comments as $comment) {
            if (isset($comment['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($comment['relationships'][$relationship]['data']['id']) ||
                        (isset($comment['relationships'][$relationship]['data']) && is_array($comment['relationships'][$relationship]['data']))
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
     * @param int $totalComments
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalComments, Command $command): void
    {
        if ($totalComments > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalComments) * 100, 2);
                $command->info("Comments with {$relationship} relationship: {$count} ({$percentage}%)");
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