<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchPaymentReminders extends AbstractAction
{
    /**
     * Define include relationships for payment reminders
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'creator',
        'updater',
        'invoice',
        'payment_reminder_sequence',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['creator'],
        ['updater'],
        ['invoice'],
        ['payment_reminder_sequence'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch payment reminders from the Productive API
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
                'payment_reminders' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching payment reminders...');
            }

            $allReminders = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("payment_reminders", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if(!$response->successful()) {
                        throw new \Exception("Failed to fetch payment reminders: " . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'payment_reminders' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $reminders = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $reminders = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $reminders,
                        'command' => $command
                    ]);

                    $allReminders = array_merge($allReminders, $reminders);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($reminders) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching payment reminders page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($reminders) . " payment reminders from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch payment reminders page {$page}: " . $e->getMessage());
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
                        'payment_reminders' => [],
                        'error' => 'Error fetching payment reminders page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allReminders) . ' payment reminders in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allReminders);
                $this->logRelationshipStats($relationshipStats, count($allReminders), $command);
            }

            return [
                'success' => true,
                'payment_reminders' => $allReminders
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in payment reminders fetch process: " . $e->getMessage());
            }

            Log::error("Error in payment reminders fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'payment_reminders' => [],
                'error' => 'Error in payment reminders fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for payment reminders
     *
     * @param array $reminders
     * @return array
     */
    protected function calculateRelationshipStats(array $reminders): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($reminders as $reminder) {
            if (isset($reminder['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($reminder['relationships'][$relationship]['data']['id'])) {
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
     * @param int $totalReminders
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalReminders, Command $command): void
    {
        if ($totalReminders > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalReminders) * 100, 2);
                $command->info("Payment reminders with {$relationship} relationship: {$count} ({$percentage}%)");
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