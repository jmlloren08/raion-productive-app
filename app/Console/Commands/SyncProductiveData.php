<?php

namespace App\Console\Commands;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncProductiveData extends Command
{
    protected $signature = 'sync:productive';
    protected $description = 'Sync data from Productive.io API';

    private $apiUrl;
    private $client;
    private $data = [
        'companies' => [],
        'projects' => [],
        'deals' => []
    ];
    public function handle(): int
    {
        $this->info('Starting Productive.io data sync...');
        $startTime = microtime(true);

        try {
            // Debug: Output configuration values
            $this->info('API URL: ' . config('services.productive.api_url'));
            $this->info('API Token: ' . (config('services.productive.api_token') ? 'Set' : 'Not set'));
            $this->info('Organization ID: ' . config('services.productive.organization_id'));

            // 1. Initialize API client
            $this->initializeClient();

            // 2. Fetch all data from Productive API
            $this->info('Fetching data from Productive API...');

            $companyFetchSuccess = $this->fetchCompanies();
            if (!$companyFetchSuccess) {
                $this->error('Failed to fetch companies. Aborting sync process.');
                return 1;
            }

            $projectFetchSuccess = $this->fetchProjects();
            if (!$projectFetchSuccess) {
                $this->error('Failed to fetch projects. Aborting sync process.');
                return 1;
            }

            $dealFetchSuccess = $this->fetchDeals();
            if (!$dealFetchSuccess) {
                $this->error('Failed to fetch deals. Aborting sync process.');
                return 1;
            }

            // 3. Store data in MySQL
            $this->info('Storing data in database...');
            $storageSuccess = $this->storeData();
            if (!$storageSuccess) {
                $this->error('Failed to store data in database. Aborting sync process.');
                return 1;
            }

            // 4. Validate data integrity
            $this->info('Validating data integrity...');
            $integrityStats = $this->validateDataIntegrity();

            // 5. Report statistics
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $this->info('==== Sync Summary ====');
            $this->info('Companies synced: ' . count($this->data['companies']));
            $this->info('Projects synced: ' . count($this->data['projects']));
            $this->info('Deals synced: ' . count($this->data['deals']));
            $this->info('Execution time: ' . $executionTime . ' seconds');
            $this->info('Sync completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
    private function initializeClient(): void
    {
        $this->apiUrl = config('services.productive.api_url');
        $apiToken = config('services.productive.api_token');
        $organizationId = config('services.productive.organization_id');

        if (!$apiToken) {
            throw new \RuntimeException('Productive.io API token not configured');
        }

        if (!$organizationId) {
            throw new \RuntimeException('Productive.io Organization ID not configured');
        }

        $this->client = Http::withoutVerifying()
            ->timeout(60) // Increase timeout to 60 seconds
            ->retry(3, 5000) // Retry 3 times with 5 second delay between attempts
            ->withHeaders([
                'X-Auth-Token' => $apiToken,
                'X-Organization-Id' => $organizationId,
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
            ]);
    }
    private function fetchCompanies(): bool
    {
        try {
            $this->info('Fetching companies...');

            $allCompanies = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for companies
                    // Add include parameter to get nested relationships, but be careful with supported includes
                    $response = $this->client->get("{$this->apiUrl}/companies", [
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name',
                    ])->throw();

                    $responseBody = $response->json();
                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        $this->error("Invalid API response format on page {$page}. Missing 'data' array.");
                        // Log the response format for troubleshooting
                        $this->warn("Response format: " . json_encode(array_keys($responseBody)));
                        continue; // Skip this page and try the next one
                    }

                    $companies = $responseBody['data'];

                    // Process included data if available
                    $companies = $this->processIncludedData($responseBody, $companies);

                    $allCompanies = array_merge($allCompanies, $companies);

                    // Check if we need to fetch more pages
                    if (count($companies) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        $this->info("Fetching companies page {$page}...");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to fetch companies page {$page}: " . $e->getMessage());
                    if ($page > 1) {
                        // We already have some data, so we can continue with what we have
                        $this->warn("Proceeding with partial data ({$page} pages fetched)");
                        $hasMorePages = false;
                    } else {
                        // First page failed, cannot continue
                        throw $e;
                    }
                }
            }

            $this->data['companies'] = $allCompanies;
            $this->info('Found ' . count($this->data['companies']) . ' companies in total');

            // Validate the format of each company
            $validCompanies = 0;
            $invalidCompanies = 0;
            foreach ($this->data['companies'] as $company) {
                if (!isset($company['id']) || !isset($company['attributes']['name'])) {
                    $this->warn("Found company with invalid format: " . json_encode($company));
                    $invalidCompanies++;
                } else {
                    $validCompanies++;
                }
            }

            if ($invalidCompanies > 0) {
                $this->warn("Found {$invalidCompanies} companies with invalid format out of " . count($this->data['companies']));
            }

            return true;
        } catch (\Exception $e) {
            $this->error('Failed to fetch companies: ' . $e->getMessage());
            if ($e instanceof \Illuminate\Http\Client\RequestException) {
                $this->error('Response: ' . $e->response->body());
            }
            return false;
        }
    }
    private function fetchProjects(): bool
    {
        try {
            $this->info('Fetching projects...');

            $allProjects = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = 'company'; // Include company relationships

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for projects
                    $response = $this->client->get("{$this->apiUrl}/projects", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ])->throw();

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        $this->error("Invalid API response format on page {$page}. Missing 'data' array.");
                        // Log the response format for troubleshooting
                        $this->warn("Response format: " . json_encode(array_keys($responseBody)));
                        continue; // Skip this page and try the next one
                    }

                    $projects = $responseBody['data'];

                    // Process included data if available
                    $projects = $this->processIncludedData($responseBody, $projects);

                    $allProjects = array_merge($allProjects, $projects);

                    // If 'included' data is present, log it for debugging
                    if (isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $includedTypes = [];
                        foreach ($responseBody['included'] as $included) {
                            $type = $included['type'] ?? 'unknown';
                            $includedTypes[$type] = ($includedTypes[$type] ?? 0) + 1;
                        }
                        $this->info("Page {$page} included data: " . json_encode($includedTypes));
                    }

                    // Check if we need to fetch more pages
                    if (count($projects) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        $this->info("Fetching projects page {$page}...");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to fetch projects page {$page}: " . $e->getMessage());

                    // If 'include' parameter is causing problems, try without it
                    if (strpos($e->getMessage(), 'include') !== false && $includeParam !== '') {
                        $this->warn("Retrying without include parameter");
                        $includeParam = '';
                        continue; // Retry the current page without include parameter
                    }

                    if ($page > 1) {
                        // We already have some data, so we can continue with what we have
                        $this->warn("Proceeding with partial data ({$page} pages fetched)");
                        $hasMorePages = false;
                    } else {
                        // First page failed, cannot continue
                        throw $e;
                    }
                }
            }

            $this->data['projects'] = $allProjects;
            $this->info('Found ' . count($this->data['projects']) . ' projects in total');

            // Count projects with company relationships
            $projectsWithCompany = 0;
            $missingCompanyInfo = [];
            foreach ($this->data['projects'] as $project) {
                if (isset($project['relationships']['company']['data']['id'])) {
                    $projectsWithCompany++;
                } else {
                    // Record projects missing company info (up to 5 for logging)
                    if (count($missingCompanyInfo) < 5) {
                        $missingCompanyInfo[] = [
                            'id' => $project['id'] ?? 'unknown',
                            'name' => $project['attributes']['name'] ?? 'unnamed'
                        ];
                    }
                }
            }

            $this->info("Projects with company relationship: {$projectsWithCompany} of " . count($this->data['projects']) .
                " (" . round(($projectsWithCompany / count($this->data['projects'])) * 100, 2) . "%)");

            if (!empty($missingCompanyInfo)) {
                $this->warn("Examples of projects missing company relationship: " . json_encode($missingCompanyInfo));
            }

            return true;
        } catch (\Exception $e) {
            $this->error('Failed to fetch projects: ' . $e->getMessage());
            if ($e instanceof \Illuminate\Http\Client\RequestException) {
                $this->error('Response: ' . $e->response->body());
            }
            return false;
        }
    }
    private function fetchDeals(): bool
    {
        try {
            $this->info('Fetching deals...');

            $allDeals = [];
            $page = 1;
            $pageSize = 100;
            $hasMorePages = true;
            $includeParam = 'company,project'; // Include both company and project relationships

            while ($hasMorePages) {
                try {
                    // Following Productive API docs for deals
                    $response = $this->client->get("{$this->apiUrl}/deals", [
                        'include' => $includeParam,
                        'page' => [
                            'number' => $page,
                            'size' => $pageSize
                        ],
                        'sort' => 'name'
                    ])->throw();

                    $responseBody = $response->json();

                    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
                        $this->error("Invalid API response format on page {$page}. Missing 'data' array.");
                        // Log the response format for troubleshooting
                        $this->warn("Response format: " . json_encode(array_keys($responseBody)));
                        continue; // Skip this page and try the next one
                    }

                    $deals = $responseBody['data'];

                    // Process included data if available
                    $deals = $this->processIncludedData($responseBody, $deals);

                    $allDeals = array_merge($allDeals, $deals);

                    // If 'included' data is present, log it for debugging
                    if (isset($responseBody['included']) && is_array($responseBody['included'])) {
                        $includedTypes = [];
                        foreach ($responseBody['included'] as $included) {
                            $type = $included['type'] ?? 'unknown';
                            $includedTypes[$type] = ($includedTypes[$type] ?? 0) + 1;
                        }
                        $this->info("Page {$page} included data: " . json_encode($includedTypes));
                    }

                    // Check if we need to fetch more pages
                    if (count($deals) < $pageSize) {
                        $hasMorePages = false;
                    } else {
                        $page++;
                        $this->info("Fetching deals page {$page}...");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to fetch deals page {$page}: " . $e->getMessage());

                    // If 'include' parameter is causing problems, try with fewer includes
                    if (strpos($e->getMessage(), 'include') !== false) {
                        if ($includeParam === 'company,project') {
                            $this->warn("Retrying with only 'company' include parameter");
                            $includeParam = 'company';
                            continue; // Retry the current page with only company
                        } else if ($includeParam === 'company') {
                            $this->warn("Retrying with only 'project' include parameter");
                            $includeParam = 'project';
                            continue; // Retry with only project
                        } else if ($includeParam === 'project') {
                            $this->warn("Retrying without include parameters");
                            $includeParam = '';
                            continue; // Retry without includes
                        }
                    }

                    if ($page > 1) {
                        // We already have some data, so we can continue with what we have
                        $this->warn("Proceeding with partial data ({$page} pages fetched)");
                        $hasMorePages = false;
                    } else {
                        // First page failed, cannot continue
                        throw $e;
                    }
                }
            }

            $this->data['deals'] = $allDeals;
            $this->info('Found ' . count($this->data['deals']) . ' deals in total');

            // Count deals with company and project relationships
            $dealsWithCompany = 0;
            $dealsWithProject = 0;
            $dealsWithBoth = 0;

            foreach ($this->data['deals'] as $deal) {
                $hasCompany = isset($deal['relationships']['company']['data']['id']);
                $hasProject = isset($deal['relationships']['project']['data']['id']);

                if ($hasCompany) $dealsWithCompany++;
                if ($hasProject) $dealsWithProject++;
                if ($hasCompany && $hasProject) $dealsWithBoth++;
            }

            $totalDeals = count($this->data['deals']);
            if ($totalDeals > 0) {
                $this->info("Deals with company relationship: {$dealsWithCompany} of {$totalDeals} (" .
                    round(($dealsWithCompany / $totalDeals) * 100, 2) . "%)");
                $this->info("Deals with project relationship: {$dealsWithProject} of {$totalDeals} (" .
                    round(($dealsWithProject / $totalDeals) * 100, 2) . "%)");
                $this->info("Deals with both company and project: {$dealsWithBoth} of {$totalDeals} (" .
                    round(($dealsWithBoth / $totalDeals) * 100, 2) . "%)");
            }

            return true;
        } catch (\Exception $e) {
            $this->error('Failed to fetch deals: ' . $e->getMessage());
            if ($e instanceof \Illuminate\Http\Client\RequestException) {
                $this->error('Response: ' . $e->response->body());
            }
            return false;
        }
    }
    private function storeData(): bool
    {
        try {
            DB::beginTransaction();

            // First validate that we have data to store
            if (empty($this->data['companies'])) {
                $this->warn('No companies fetched from Productive API. Skipping company storage.');
            }
            if (empty($this->data['projects'])) {
                $this->warn('No projects fetched from Productive API. Skipping project storage.');
            }
            if (empty($this->data['deals'])) {
                $this->warn('No deals fetched from Productive API. Skipping deal storage.');
            }

            // Store companies first
            $this->info('Storing companies...');
            $companiesSuccess = 0;
            $companiesError = 0;
            foreach ($this->data['companies'] as $companyData) {
                try {
                    $this->storeCompany($companyData);
                    $companiesSuccess++;
                } catch (\Exception $e) {
                    $this->error("Failed to store company (ID: {$companyData['id']}): " . $e->getMessage());
                    $companiesError++;
                }
            }
            $this->info("Companies: {$companiesSuccess} stored successfully, {$companiesError} failed");

            // Then store projects (which depend on companies)
            $this->info('Storing projects...');
            $projectsWithCompany = 0;
            $projectsSuccess = 0;
            $projectsError = 0;
            foreach ($this->data['projects'] as $projectData) {
                try {
                    $this->storeProject($projectData);
                    $projectsSuccess++;
                    if (isset($projectData['relationships']['company']['data']['id'])) {
                        $projectsWithCompany++;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to store project (ID: {$projectData['id']}): " . $e->getMessage());
                    $projectsError++;
                }
            }
            $this->info("Projects: {$projectsSuccess} stored successfully, {$projectsError} failed");
            if ($projectsSuccess > 0) {
                $this->info('Projects with company relationship: ' . $projectsWithCompany . ' (' . round(($projectsWithCompany / $projectsSuccess) * 100, 2) . '%)');
            }

            // Finally store deals (which depend on both companies and projects)
            $this->info('Storing deals...');
            $dealsWithCompany = 0;
            $dealsWithProject = 0;
            $dealsSuccess = 0;
            $dealsError = 0;
            foreach ($this->data['deals'] as $dealData) {
                try {
                    $this->storeDeal($dealData);
                    $dealsSuccess++;
                    if (isset($dealData['relationships']['company']['data']['id'])) {
                        $dealsWithCompany++;
                    }
                    if (isset($dealData['relationships']['project']['data']['id'])) {
                        $dealsWithProject++;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to store deal (ID: {$dealData['id']}): " . $e->getMessage());
                    $dealsError++;
                }
            }
            $this->info("Deals: {$dealsSuccess} stored successfully, {$dealsError} failed");
            if ($dealsSuccess > 0) {
                $this->info('Deals with company relationship: ' . $dealsWithCompany . ' (' . round(($dealsWithCompany / $dealsSuccess) * 100, 2) . '%)');
                $this->info('Deals with project relationship: ' . $dealsWithProject . ' (' . round(($dealsWithProject / $dealsSuccess) * 100, 2) . '%)');
            }

            // Check if we had any errors and warn the user
            $totalErrors = $companiesError + $projectsError + $dealsError;
            if ($totalErrors > 0) {
                $this->warn("Completed with {$totalErrors} errors. Some records may not have been stored correctly.");
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to store data: ' . $e->getMessage());
            return false;
        }
    }
    private function storeCompany(array $companyData): void
    {
        $attributes = $companyData['attributes'] ?? [];
        $relationships = $companyData['relationships'] ?? [];

        // Extract contact information if available
        $contact = $attributes['contact'] ?? [];
        
        // Prepare data with safe fallbacks for all fields
        $data = [
            'id' => $companyData['id'],
            'type' => $companyData['type'] ?? 'companies',
            'name' => $attributes['name'] ?? 'Unknown Company',
            'billing_name' => $attributes['billing_name'] ?? null,
            'vat' => $attributes['vat'] ?? null,
            'default_currency' => $attributes['default_currency'] ?? null,
            'created_at_api' => $attributes['created_at'] ?? null,
            'last_activity_at' => $attributes['last_activity_at'] ?? null,
            'archived_at' => $attributes['archived_at'] ?? null,
            'avatar_url' => $attributes['avatar_url'] ?? null,
            
            // JSON fields
            'invoice_email_recipients' => is_array($attributes['invoice_email_recipients']) 
                ? json_encode($attributes['invoice_email_recipients']) 
                : $attributes['invoice_email_recipients'] ?? null,
            'custom_fields' => is_array($attributes['custom_fields']) 
                ? json_encode($attributes['custom_fields']) 
                : $attributes['custom_fields'] ?? null,
            'contact' => is_array($contact) 
                ? json_encode($contact) 
                : $contact,
            'settings' => is_array($attributes['settings']) 
                ? json_encode($attributes['settings']) 
                : $attributes['settings'] ?? null,
                
            // Identifiers and codes
            'company_code' => $attributes['company_code'] ?? null,
            'domain' => $attributes['domain'] ?? null,
            'projectless_budgets' => $attributes['projectless_budgets'] ?? false,
            'leitweg_id' => $attributes['leitweg_id'] ?? null,
            'buyer_reference' => $attributes['buyer_reference'] ?? null,
            'peppol_id' => $attributes['peppol_id'] ?? null,
            
            // Foreign key references
            'default_subsidiary_id' => $attributes['default_subsidiary_id'] ?? 
                (isset($relationships['default_subsidiary']['data']['id']) ? $relationships['default_subsidiary']['data']['id'] : null),
            'default_tax_rate_id' => $attributes['default_tax_rate_id'] ?? 
                (isset($relationships['default_tax_rate']['data']['id']) ? $relationships['default_tax_rate']['data']['id'] : null),
            'default_document_type_id' => $attributes['default_document_type_id'] ?? null,
            
            // Description and payment terms
            'description' => $attributes['description'] ?? null,
            'due_days' => $attributes['due_days'] ?? null,
            
            // Tags
            'tag_list' => is_array($attributes['tag_list']) 
                ? json_encode($attributes['tag_list']) 
                : $attributes['tag_list'] ?? null,
            
            // Metadata
            'sample_data' => $attributes['sample_data'] ?? false,
            'external_id' => $attributes['external_id'] ?? null,
            'external_sync' => $attributes['external_sync'] ?? false,
        ];

        try {
            ProductiveCompany::updateOrCreate(
                ['id' => $companyData['id']],
                $data
            );
            
            $this->info("Stored company '{$attributes['name']}' (ID: {$companyData['id']})");
        } catch (\Exception $e) {
            $this->error("Failed to store company '{$attributes['name']}' (ID: {$companyData['id']}): " . $e->getMessage());
            // Log additional details for troubleshooting
            $this->warn("Company data: " . json_encode([
                'id' => $companyData['id'],
                'name' => $attributes['name'] ?? 'Unknown Company'
            ]));
        }
    }
    private function storeProject(array $projectData): void
    {
        $companyId = null;
        if (isset($projectData['relationships']['company']['data']['id'])) {
            $companyId = $projectData['relationships']['company']['data']['id'];
            // Check if company exists in our database
            $companyExists = ProductiveCompany::where('id', $companyId)->exists();
            if (!$companyExists) {
                $this->warn("Project '{$projectData['attributes']['name']}' is linked to company ID: {$companyId}, but this company doesn't exist in our database.");
                $companyId = null; // Reset to avoid foreign key constraint failure
            } else {
                $this->info("Project '{$projectData['attributes']['name']}' is linked to company ID: {$companyId}");
            }
        } else {
            $this->warn("Project '{$projectData['attributes']['name']}' has no company relationship");
        }

        $attributes = $projectData['attributes'] ?? [];

        // Prepare data with safe fallbacks for all fields
        $data = [
            'id' => $projectData['id'],
            'company_id' => $companyId,
            'type' => $projectData['type'] ?? 'projects',
            'name' => $attributes['name'] ?? 'Unknown Project',
            'number' => $attributes['number'] ?? null,
            'project_number' => $attributes['project_number'] ?? $attributes['number'] ?? null,
            'project_type_id' => $attributes['project_type_id'] ?? null,
            'project_color_id' => $attributes['project_color_id'] ?? null,
            'last_activity_at' => $attributes['last_activity_at'] ?? null,
            'archived_at' => $attributes['archived_at'] ?? null,
            'created_at_api' => $attributes['created_at'] ?? null,
            
            // Boolean fields
            'public_access' => $attributes['public_access'] ?? false,
            'time_on_tasks' => $attributes['time_on_tasks'] ?? false,
            'template' => $attributes['template'] ?? false,
            'sample_data' => $attributes['sample_data'] ?? false,
            
            // JSON fields
            'preferences' => is_array($attributes['preferences']) 
                ? json_encode($attributes['preferences']) 
                : $attributes['preferences'] ?? null,
            'tag_colors' => is_array($attributes['tag_colors']) 
                ? json_encode($attributes['tag_colors']) 
                : $attributes['tag_colors'] ?? null,
            'custom_fields' => is_array($attributes['custom_fields']) 
                ? json_encode($attributes['custom_fields']) 
                : $attributes['custom_fields'] ?? null,
            'task_custom_fields_ids' => is_array($attributes['task_custom_fields_ids']) 
                ? json_encode($attributes['task_custom_fields_ids']) 
                : $attributes['task_custom_fields_ids'] ?? null,
            'task_custom_fields_positions' => is_array($attributes['task_custom_fields_positions']) 
                ? json_encode($attributes['task_custom_fields_positions']) 
                : $attributes['task_custom_fields_positions'] ?? null,
        ];

        try {
            ProductiveProject::updateOrCreate(
                ['id' => $projectData['id']],
                $data
            );
            
            $this->info("Stored project '{$attributes['name']}' (ID: {$projectData['id']})");
        } catch (\Exception $e) {
            $this->error("Failed to store project '{$attributes['name']}' (ID: {$projectData['id']}): " . $e->getMessage());
            // Log additional details for troubleshooting
            $this->warn("Project data: " . json_encode([
                'id' => $projectData['id'],
                'company_id' => $companyId,
                'name' => $attributes['name'] ?? 'Unknown Project'
            ]));
        }
    }
    private function storeDeal(array $dealData): void
    {
        $companyId = null;
        if (isset($dealData['relationships']['company']['data']['id'])) {
            $companyId = $dealData['relationships']['company']['data']['id'];
            // Check if company exists in our database
            $companyExists = ProductiveCompany::where('id', $companyId)->exists();
            if (!$companyExists) {
                $this->warn("Deal '{$dealData['attributes']['name']}' is linked to company ID: {$companyId}, but this company doesn't exist in our database.");
                $companyId = null; // Reset to avoid foreign key constraint failure
            } else {
                $this->info("Deal '{$dealData['attributes']['name']}' is linked to company ID: {$companyId}");
            }
        } else {
            $this->warn("Deal '{$dealData['attributes']['name']}' has no company relationship");
        }

        $projectId = null;
        if (isset($dealData['relationships']['project']['data']['id'])) {
            $projectId = $dealData['relationships']['project']['data']['id'];
            // Check if project exists in our database
            $projectExists = ProductiveProject::where('id', $projectId)->exists();
            if (!$projectExists) {
                $this->warn("Deal '{$dealData['attributes']['name']}' is linked to project ID: {$projectId}, but this project doesn't exist in our database.");
                $projectId = null; // Reset to avoid foreign key constraint failure
            } else {
                $this->info("Deal '{$dealData['attributes']['name']}' is linked to project ID: {$projectId}");
            }
        } else {
            $this->warn("Deal '{$dealData['attributes']['name']}' has no project relationship");
        }

        $attributes = $dealData['attributes'] ?? [];

        // Prepare data with safe fallbacks for all fields
        $data = [
            'id' => $dealData['id'],
            'type' => $dealData['type'] ?? 'deals',
            'name' => $attributes['name'] ?? 'Unknown Deal',
            
            // Foreign keys
            'company_id' => $companyId,
            'project_id' => $projectId,
            
            // Basic identifiers
            'number' => $attributes['number'] ?? null,
            'deal_number' => $attributes['deal_number'] ?? null,
            'suffix' => $attributes['suffix'] ?? null,
            'email_key' => $attributes['email_key'] ?? null,
            'position' => $attributes['position'] ?? null,
            'purchase_order_number' => $attributes['purchase_order_number'] ?? null,
            
            // Dates
            'date' => $attributes['date'] ?? null,
            'end_date' => $attributes['end_date'] ?? null,
            'closed_at' => $attributes['closed_at'] ?? null,
            'delivered_on' => $attributes['delivered_on'] ?? null,
            'last_activity_at' => $attributes['last_activity_at'] ?? null,
            'sales_status_updated_at' => $attributes['sales_status_updated_at'] ?? null,
            'sales_closed_at' => $attributes['sales_closed_at'] ?? null,
            'sales_closed_on' => $attributes['sales_closed_on'] ?? null,
            'exchange_date' => $attributes['exchange_date'] ?? null,
            'created_at_api' => $attributes['created_at'] ?? null,
            
            // Approval flags
            'time_approval' => $attributes['time_approval'] ?? false,
            'expense_approval' => $attributes['expense_approval'] ?? false,
            'client_access' => $attributes['client_access'] ?? false,
            'budget' => $attributes['budget'] ?? false,
            'service_type_restricted_tracking' => $attributes['service_type_restricted_tracking'] ?? false,
            'validate_expense_when_closing' => $attributes['validate_expense_when_closing'] ?? false,
            'sample_data' => $attributes['sample_data'] ?? false,
            'external_sync' => $attributes['external_sync'] ?? false,
            
            // Type IDs
            'deal_type_id' => $attributes['deal_type_id'] ?? null,
            'tracking_type_id' => $attributes['tracking_type_id'] ?? null,
            'rounding_interval_id' => $attributes['rounding_interval_id'] ?? null,
            'rounding_method_id' => $attributes['rounding_method_id'] ?? null,
            'manual_invoicing_status_id' => $attributes['manual_invoicing_status_id'] ?? null,
            
            // Position and exchange rate
            'position' => $attributes['position'] ?? null,
            'exchange_rate' => $attributes['exchange_rate'] ?? null,
            
            // JSON fields
            'custom_fields' => is_array($attributes['custom_fields']) 
                ? json_encode($attributes['custom_fields']) 
                : $attributes['custom_fields'] ?? null,
            'editor_config' => is_array($attributes['editor_config']) 
                ? json_encode($attributes['editor_config']) 
                : $attributes['editor_config'] ?? null,
                
            // Financials
            'discount' => $attributes['discount'] ?? null,
            'revenue' => $attributes['revenue'] ?? null,
            'revenue_default' => $attributes['revenue_default'] ?? null,
            'revenue_normalized' => $attributes['revenue_normalized'] ?? null,
            'services_revenue' => $attributes['services_revenue'] ?? null,
            'services_revenue_default' => $attributes['services_revenue_default'] ?? null,
            'services_revenue_normalized' => $attributes['services_revenue_normalized'] ?? null,
            'budget_total' => $attributes['budget_total'] ?? null,
            'budget_total_default' => $attributes['budget_total_default'] ?? null,
            'budget_total_normalized' => $attributes['budget_total_normalized'] ?? null,
            'budget_used' => $attributes['budget_used'] ?? null,
            'budget_used_default' => $attributes['budget_used_default'] ?? null,
            'budget_used_normalized' => $attributes['budget_used_normalized'] ?? null,
            'projected_revenue' => $attributes['projected_revenue'] ?? null,
            'projected_revenue_default' => $attributes['projected_revenue_default'] ?? null,
            'projected_revenue_normalized' => $attributes['projected_revenue_normalized'] ?? null,
            'invoiced' => $attributes['invoiced'] ?? null,
            'invoiced_default' => $attributes['invoiced_default'] ?? null,
            'invoiced_normalized' => $attributes['invoiced_normalized'] ?? null,
            'pending_invoicing' => $attributes['pending_invoicing'] ?? null,
            'pending_invoicing_default' => $attributes['pending_invoicing_default'] ?? null,
            'pending_invoicing_normalized' => $attributes['pending_invoicing_normalized'] ?? null,
            'manually_invoiced' => $attributes['manually_invoiced'] ?? null,
            'manually_invoiced_default' => $attributes['manually_invoiced_default'] ?? null,
            'manually_invoiced_normalized' => $attributes['manually_invoiced_normalized'] ?? null,
            'draft_invoiced' => $attributes['draft_invoiced'] ?? null,
            'draft_invoiced_default' => $attributes['draft_invoiced_default'] ?? null,
            'draft_invoiced_normalized' => $attributes['draft_invoiced_normalized'] ?? null,
            'amount_credited' => $attributes['amount_credited'] ?? null,
            'amount_credited_default' => $attributes['amount_credited_default'] ?? null,
            'amount_credited_normalized' => $attributes['amount_credited_normalized'] ?? null,
            'expense' => $attributes['expense'] ?? null,
            'expense_default' => $attributes['expense_default'] ?? null,
            'expense_normalized' => $attributes['expense_normalized'] ?? null,
            
            // Currencies
            'currency' => $attributes['currency'] ?? null,
            'currency_default' => $attributes['currency_default'] ?? null,
            'currency_normalized' => $attributes['currency_normalized'] ?? null,
            
            // Tracking & budgeting
            'man_day_minutes' => $attributes['man_day_minutes'] ?? null,
            'billable_time' => $attributes['billable_time'] ?? null,
            'budget_warning' => $attributes['budget_warning'] ?? null,
            'estimated_time' => $attributes['estimated_time'] ?? null,
            'budgeted_time' => $attributes['budgeted_time'] ?? null,
            'worked_time' => $attributes['worked_time'] ?? null,
            'time_to_close' => $attributes['time_to_close'] ?? null,
            'probability' => $attributes['probability'] ?? null,
            'previous_probability' => $attributes['previous_probability'] ?? null,
            'todo_count' => $attributes['todo_count'] ?? 0,
            'todo_due_date' => $attributes['todo_due_date'] ?? null,
            
            // Notes
            'note' => $attributes['note'] ?? null,
            'proposal_note' => $attributes['proposal_note'] ?? null,
            'note_interpolated' => $attributes['note_interpolated'] ?? null,
            'proposal_note_interpolated' => $attributes['proposal_note_interpolated'] ?? null,
            'lost_comment' => $attributes['lost_comment'] ?? null,
            
            // External ID
            'external_id' => $attributes['external_id'] ?? null,
        ];

        try {
            ProductiveDeal::updateOrCreate(
                ['id' => $dealData['id']],
                $data
            );
            
            $this->info("Stored deal '{$attributes['name']}' (ID: {$dealData['id']})");
        } catch (\Exception $e) {
            $this->error("Failed to store deal '{$attributes['name']}' (ID: {$dealData['id']}): " . $e->getMessage());
            // Log additional details for troubleshooting
            $this->warn("Deal data: " . json_encode([
                'id' => $dealData['id'],
                'company_id' => $companyId,
                'project_id' => $projectId,
                'name' => $attributes['name'] ?? 'Unknown Deal'
            ]));
            
            // If it's an SQL error with column info, log it for debugging
            if (strpos($e->getMessage(), 'SQL') !== false) {
                $this->warn("SQL Error: " . $e->getMessage());
            }
        }
    }
    /**
     * Validate the data integrity after syncing
     * This checks that relationships between entities are properly maintained
     */
    private function validateDataIntegrity(): array
    {
        $this->info('Validating data integrity...');

        $stats = [
            'companies' => [
                'total' => ProductiveCompany::count(),
                'with_projects' => 0,
                'with_deals' => 0
            ],
            'projects' => [
                'total' => ProductiveProject::count(),
                'with_company' => 0,
                'with_deals' => 0,
                'orphaned' => 0
            ],
            'deals' => [
                'total' => ProductiveDeal::count(),
                'with_company' => 0,
                'with_project' => 0,
                'with_both' => 0,
                'orphaned' => 0
            ]
        ];

        // Check companies with related projects and deals
        $companiesWithProjects = ProductiveCompany::has('projects')->count();
        $companiesWithDeals = ProductiveCompany::has('deals')->count();

        $stats['companies']['with_projects'] = $companiesWithProjects;
        $stats['companies']['with_deals'] = $companiesWithDeals;

        // Check projects with company and deals
        $projectsWithCompany = ProductiveProject::whereNotNull('company_id')->count();
        $projectsWithDeals = ProductiveProject::has('deals')->count();
        $orphanedProjects = ProductiveProject::whereNull('company_id')->count();

        $stats['projects']['with_company'] = $projectsWithCompany;
        $stats['projects']['with_deals'] = $projectsWithDeals;
        $stats['projects']['orphaned'] = $orphanedProjects;

        // Check deals with company and project
        $dealsWithCompany = ProductiveDeal::whereNotNull('company_id')->count();
        $dealsWithProject = ProductiveDeal::whereNotNull('project_id')->count();
        $dealsWithBoth = ProductiveDeal::whereNotNull('company_id')
            ->whereNotNull('project_id')
            ->count();
        $orphanedDeals = ProductiveDeal::whereNull('company_id')
            ->whereNull('project_id')
            ->count();

        $stats['deals']['with_company'] = $dealsWithCompany;
        $stats['deals']['with_project'] = $dealsWithProject;
        $stats['deals']['with_both'] = $dealsWithBoth;
        $stats['deals']['orphaned'] = $orphanedDeals;

        // Log the results
        $this->info('=== Data Integrity Report ===');

        // Companies
        $this->info("Companies: {$stats['companies']['total']} total");
        $this->info("- {$stats['companies']['with_projects']} have related projects (" .
            round(($stats['companies']['with_projects'] / max(1, $stats['companies']['total'])) * 100, 2) . "%)");
        $this->info("- {$stats['companies']['with_deals']} have related deals (" .
            round(($stats['companies']['with_deals'] / max(1, $stats['companies']['total'])) * 100, 2) . "%)");

        // Projects
        $this->info("Projects: {$stats['projects']['total']} total");
        $this->info("- {$stats['projects']['with_company']} have a company (" .
            round(($stats['projects']['with_company'] / max(1, $stats['projects']['total'])) * 100, 2) . "%)");
        $this->info("- {$stats['projects']['with_deals']} have related deals (" .
            round(($stats['projects']['with_deals'] / max(1, $stats['projects']['total'])) * 100, 2) . "%)");
        $this->info("- {$stats['projects']['orphaned']} are orphaned (no company) (" .
            round(($stats['projects']['orphaned'] / max(1, $stats['projects']['total'])) * 100, 2) . "%)");

        // Deals
        $this->info("Deals: {$stats['deals']['total']} total");
        $this->info("- {$stats['deals']['with_company']} have a company (" .
            round(($stats['deals']['with_company'] / max(1, $stats['deals']['total'])) * 100, 2) . "%)");
        $this->info("- {$stats['deals']['with_project']} have a project (" .
            round(($stats['deals']['with_project'] / max(1, $stats['deals']['total'])) * 100, 2) . "%)");
        $this->info("- {$stats['deals']['with_both']} have both company and project (" .
            round(($stats['deals']['with_both'] / max(1, $stats['deals']['total'])) * 100, 2) . "%)");
        $this->info("- {$stats['deals']['orphaned']} are orphaned (no company or project) (" .
            round(($stats['deals']['orphaned'] / max(1, $stats['deals']['total'])) * 100, 2) . "%)");

        return $stats;
    }

    /**
     * Process included relationships from API response to extract related entity details
     * 
     * @param array $responseBody API response containing 'included' data
     * @param array $resources The resources to enrich with included data
     * @return array The enriched resources with included data
     */
    private function processIncludedData(array $responseBody, array $resources): array
    {
        if (!isset($responseBody['included']) || !is_array($responseBody['included'])) {
            return $resources;
        }

        $this->info("Processing " . count($responseBody['included']) . " included resources");

        // Create a map of included resources
        $includedMap = [];
        $includedTypes = [];
        foreach ($responseBody['included'] as $included) {
            $resourceType = $included['type'] ?? 'unknown';
            $resourceId = $included['id'] ?? 'unknown';
            if ($resourceType !== 'unknown' && $resourceId !== 'unknown') {
                $includedMap["{$resourceType}:{$resourceId}"] = $included;
                $includedTypes[$resourceType] = ($includedTypes[$resourceType] ?? 0) + 1;
            }
        }

        // Log the types of included resources
        foreach ($includedTypes as $type => $count) {
            $this->info("Found {$count} included resources of type '{$type}'");
        }

        // Enrich resources with included entities
        foreach ($resources as &$resource) {
            if (isset($resource['relationships'])) {
                foreach ($resource['relationships'] as $relName => $relData) {
                    if (isset($relData['data'])) {
                        // Handle single relationship
                        if (!isset($relData['data'][0])) {
                            $relType = $relData['data']['type'] ?? null;
                            $relId = $relData['data']['id'] ?? null;
                            if ($relType && $relId) {
                                $mapKey = "{$relType}:{$relId}";
                                if (isset($includedMap[$mapKey])) {
                                    // Add the included data to the relationship
                                    $resource['relationships'][$relName]['included'] = $includedMap[$mapKey];

                                    // If this included entity also has relationships, process them
                                    $includedEntity = $includedMap[$mapKey];
                                    if (isset($includedEntity['relationships'])) {
                                        foreach ($includedEntity['relationships'] as $subRelName => $subRelData) {
                                            if (isset($subRelData['data'])) {
                                                $this->processNestedRelationship(
                                                    $includedMap,
                                                    $resource['relationships'][$relName]['included']['relationships'][$subRelName],
                                                    $subRelData['data']
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        // Handle array of relationships
                        else {
                            foreach ($relData['data'] as $i => $rel) {
                                $relType = $rel['type'] ?? null;
                                $relId = $rel['id'] ?? null;
                                if ($relType && $relId) {
                                    $mapKey = "{$relType}:{$relId}";
                                    if (isset($includedMap[$mapKey])) {
                                        if (!isset($resource['relationships'][$relName]['included'])) {
                                            $resource['relationships'][$relName]['included'] = [];
                                        }

                                        // Add the included data to the relationship
                                        $resource['relationships'][$relName]['included'][] = $includedMap[$mapKey];

                                        // Process nested relationships if any
                                        $includedIndex = count($resource['relationships'][$relName]['included']) - 1;
                                        $includedEntity = $includedMap[$mapKey];

                                        if (isset($includedEntity['relationships'])) {
                                            foreach ($includedEntity['relationships'] as $subRelName => $subRelData) {
                                                if (isset($subRelData['data'])) {
                                                    $this->processNestedRelationship(
                                                        $includedMap,
                                                        $resource['relationships'][$relName]['included'][$includedIndex]['relationships'][$subRelName],
                                                        $subRelData['data']
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $resources;
    }

    /**
     * Helper method to process nested relationships
     * 
     * @param array $includedMap Map of all included resources
     * @param array &$targetRelationship The relationship to update with included data
     * @param array $relationshipData The relationship data to process
     */
    private function processNestedRelationship(array $includedMap, array &$targetRelationship, array $relationshipData): void
    {
        // Handle single relationship
        if (!isset($relationshipData[0])) {
            $relType = $relationshipData['type'] ?? null;
            $relId = $relationshipData['id'] ?? null;
            if ($relType && $relId) {
                $mapKey = "{$relType}:{$relId}";
                if (isset($includedMap[$mapKey])) {
                    // Add the included data to the relationship
                    $targetRelationship['included'] = $includedMap[$mapKey];
                }
            }
        }
        // Handle array of relationships
        else {
            foreach ($relationshipData as $i => $rel) {
                $relType = $rel['type'] ?? null;
                $relId = $rel['id'] ?? null;
                if ($relType && $relId) {
                    $mapKey = "{$relType}:{$relId}";
                    if (isset($includedMap[$mapKey])) {
                        if (!isset($targetRelationship['included'])) {
                            $targetRelationship['included'] = [];
                        }
                        // Add the included data to the relationship
                        $targetRelationship['included'][] = $includedMap[$mapKey];
                    }
                }
            }
        }
    }
}
