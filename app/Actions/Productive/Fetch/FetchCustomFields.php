<?php

namespace App\Actions\Productive\Fetch;

use App\Actions\Productive\AbstractAction;
use App\Actions\Productive\ProcessIncludedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchCustomFields extends AbstractAction
{
    /**
     * Define include relationships for custom fields
     * 
     * @var array
     */
    protected array $includeRelationships = [
        'project',
        'section',
        'survey',
        'custom_field_people',
        'options',
    ];

    /**
     * Define fallback include relationships if the full set fails
     * 
     * @var array
     */
    protected array $fallbackIncludes = [
        ['project'],
        ['section'],
        ['survey'],
        ['custom_field_people'],
        ['options'],
        []  // Empty array means no includes
    ];

    /**
     * Fetch custom fields from the Productive API
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
                'custom_fields' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching custom fields...');
            }

            $allCustomFields = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = implode(',', $this->includeRelationships);

            while ($hasMorePages) {
                try {
                    $response = $apiClient->get("custom_fields", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                    ])->throw();

                    if (!$response->successful()) {
                        throw new \Exception("Failed to fetch custom fields: " . $response->body());
                    }

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        return [
                            'success' => false,
                            'custom_fields' => [],
                            'error' => "Invalid API response format on page {$page}"
                        ];
                    }

                    $customFields = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $customFields = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $customFields,
                        'command' => $command
                    ]);

                    $allCustomFields = array_merge($allCustomFields, $customFields);

                    // Debug logging for included data
                    if ($command instanceof Command && isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $this->logIncludedData($responseBody['included'], $page, $command);
                    }

                    // Check if we need to fetch more pages
                    if (count($customFields) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching custom fields page {$page}...");
                        }
                    }

                    if ($command instanceof Command) {
                        $command->info("Fetched " . count($customFields) . " custom fields from page " . ($page - 1));
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch custom fields page {$page}: " . $e->getMessage());
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
                        'custom_fields' => [],
                        'error' => 'Error fetching custom fields page ' . $page . ': ' . $e->getMessage()
                    ];
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allCustomFields) . ' custom fields in total');

                // Calculate and log relationship stats
                $relationshipStats = $this->calculateRelationshipStats($allCustomFields);
                $this->logRelationshipStats($relationshipStats, count($allCustomFields), $command);
            }

            return [
                'success' => true,
                'custom_fields' => $allCustomFields
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Error in custom fields fetch process: " . $e->getMessage());
            }

            Log::error("Error in custom fields fetch process: " . $e->getMessage());

            return [
                'success' => false,
                'custom_fields' => [],
                'error' => 'Error in custom fields fetch process: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate relationship statistics for custom fields
     *
     * @param array $customFields
     * @return array
     */
    protected function calculateRelationshipStats(array $customFields): array
    {
        $stats = array_fill_keys($this->includeRelationships, 0);

        foreach ($customFields as $field) {
            if (isset($field['relationships'])) {
                foreach ($this->includeRelationships as $relationship) {
                    if (isset($field['relationships'][$relationship]['data']['id'])) {
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
     * @param int $totalFields
     * @param Command $command
     * @return void
     */
    protected function logRelationshipStats(array $stats, int $totalFields, Command $command): void
    {
        if ($totalFields > 0) {
            foreach ($stats as $relationship => $count) {
                $percentage = round(($count / $totalFields) * 100, 2);
                $command->info("Custom fields with {$relationship} relationship: {$count} ({$percentage}%)");
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
