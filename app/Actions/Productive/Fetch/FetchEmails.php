<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchEmails extends AbstractAction
{
    /**
     * Define include relationships for emails
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'creator',
        'deal',
        'invoice',
        'payment_reminder',
        'recipients',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'attachments',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['creator'],
        ['deal'],
        ['invoice'],
        ['payment_reminder'],
        ['recipients'],
        ['to_recipients'],
        ['cc_recipients'],
        ['bcc_recipients'],
        ['attachments'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch emails from the Productive API
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
                'emails' => [],
                'error' => 'API client is required'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching emails...');
            }

            $allEmails = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for emails
                    $response = $apiClient->get("emails", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception('Failed to fetch emails: ' . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'emails' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $emails = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $emails = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $emails,
                        'command' => $command
                    ]);

                    $allEmails = array_merge($allEmails, $emails);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($emails) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching emails page {$page}...");
                        }
                    }

                    // Log progress
                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($emails) . " emails from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch emails page {$page}: " . $e->getMessage());
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
                        'emails' => [],
                        'error' => 'Error fetching emails page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allEmails) . ' emails in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allEmails);
                $this->logRelationshipStats($relationshipStats, count($allEmails), $command);
            }

            return [
                'success' => true,
                'emails' => $allEmails
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in emails fetch process: " . $e->getMessage());
            }

            Log::error("Error in emails fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'emails' => [],
                'error' => 'Error in emails fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for emails
     *
     * @param array $emails
     * @return array
     */
    protected function calculateRelationshipStats(array $emails): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($emails as $email) {
            if (isset($email['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (
                        isset($email['relationships'][$relationship]['data']['id']) ||
                        (isset($email['relationships'][$relationship]['data']) && is_array($email['relationships'][$relationship]['data']))
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
     * @param int $totalEmails
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalEmails, Command $command): void
    {
        if ($totalEmails > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalEmails) * 100, 2);
                $command->info("Emails with {$relationship} relationship: {$count} ({$percentage}%)");
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