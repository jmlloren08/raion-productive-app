<?php

namespace App\Console\Commands;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncProductiveData extends Command
{
    protected $signature = 'sync:productive';
    protected $description = 'Sync data from Productive.io API';

    public function handle(): int
    {
        $this->info('Starting Productive.io data sync...');

        $apiUrl = config('services.productive.api_url');
        $apiToken = config('services.productive.api_token');

        if (!$apiToken) {
            $this->error('Productive.io API token not configured');
            return 1;
        }

        try {
            $client = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
            ]);

            // Fetch companies with their projects
            $companiesResponse = $client->get("{$apiUrl}/companies", [
                'include' => 'projects',
                'page' => ['size' => 200],
            ])->throw()->json();

            // Fetch projects with their companies
            $projectsResponse = $client->get("{$apiUrl}/projects", [
                'include' => 'company',
                'page' => ['size' => 200],
            ])->throw()->json();

            DB::beginTransaction();

            // Process companies
            foreach ($companiesResponse['data'] as $companyData) {
                ProductiveCompany::updateOrCreate(
                    ['id' => $companyData['id']],
                    [
                        'name' => $companyData['attributes']['name'],
                        'productive_created_at' => $companyData['attributes']['created_at'],
                        'productive_updated_at' => $companyData['attributes']['updated_at'],
                    ]
                );
            }

            // Process projects
            foreach ($projectsResponse['data'] as $projectData) {
                if (isset($projectData['relationships']['company']['data']['id'])) {
                    ProductiveProject::updateOrCreate(
                        ['id' => $projectData['id']],
                        [
                            'company_id' => $projectData['relationships']['company']['data']['id'],
                            'name' => $projectData['attributes']['name'],
                            'status' => $projectData['attributes']['status'],
                            'productive_created_at' => $projectData['attributes']['created_at'],
                            'productive_updated_at' => $projectData['attributes']['updated_at'],
                        ]
                    );
                }
            }

            DB::commit();
            $this->info('Productive.io data sync completed successfully');
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error syncing Productive.io data: ' . $e->getMessage());
            return 1;
        }
    }
}
