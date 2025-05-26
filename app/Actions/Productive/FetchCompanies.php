<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchCompanies extends AbstractAction
{
    /**
     * Fetch companies from the Productive API
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
                'companies' => [],
                'error' => 'Client not provided'
            ];
        }

        try {
            if ($command instanceof Command) {
                $command->info('Fetching companies...');
            }

            $allCompanies = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for companies
                    $response = $client->get("companies", [
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name',
                    ])->throw();

                    $responseBody = $response->json();
                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        if ($command instanceof Command) {
                            $command->error("Invalid API response format on page {$page}. Missing 'data' array.");
                            $command->warn("Response format: " . json_encode(array_keys($responseBody)));
                        }
                        continue; // Skip this page and try the next one
                    }

                    $companies = $responseBody['data'];

                    // Process included data if available
                    $processIncludedAction = new ProcessIncludedData();
                    $companies = $processIncludedAction->handle([
                        'responseBody' => $responseBody,
                        'resources' => $companies,
                        'command' => $command
                    ]);

                    $allCompanies = array_merge($allCompanies, $companies);

                    // Check if we need to fetch more pages
                    if (count($companies) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        if ($command instanceof Command) {
                            $command->info("Fetching companies page {$page}...");
                        }
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to fetch companies page {$page}: " . $e->getMessage());
                    }
                    if ($page > 1) {
                        // We already have some data, so we can continue with what we have
                        if ($command instanceof Command) {
                            $command->warn("Proceeding with partial data ({$page} pages fetched)");
                        }
                        $hasMorePages = false;
                    } else {
                        // First page failed, cannot continue
                        throw $e;
                    }
                }
            }

            if ($command instanceof Command) {
                $command->info('Found ' . count($allCompanies) . ' companies in total');

                // Validate the format of each company
                $validCompanies = 0;
                $invalidCompanies = 0;
                foreach ($allCompanies as $company) {
                    if (!isset($company['id']) || !isset($company['attributes']['name'])) {
                        if ($command instanceof Command) {
                            $command->warn("Found company with invalid format: " . json_encode($company));
                        }
                        $invalidCompanies++;
                    } else {
                        $validCompanies++;
                    }
                }

                if ($invalidCompanies > 0) {
                    if ($command instanceof Command) {
                        $command->warn("Found {$invalidCompanies} companies with invalid format out of " . count($allCompanies));
                    }
                }
            }

            return [
                'success' => true,
                'companies' => $allCompanies
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Failed to fetch companies: ' . $e->getMessage());
                if ($e instanceof \Illuminate\Http\Client\RequestException) {
                    $command->error('Response: ' . $e->response->body());
                }
            }
            return [
                'success' => false,
                'companies' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}
