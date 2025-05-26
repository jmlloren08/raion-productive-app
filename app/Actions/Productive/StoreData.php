<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StoreData extends AbstractAction
{
    /**
     * Store all fetched data in the database
     *
     * @param array $parameters
     * @return bool
     */
    public function handle(array $parameters = []): bool
    {
        $data = $parameters['data'] ?? [];
        $command = $parameters['command'] ?? null;

        try {
            DB::beginTransaction();

            // First validate that we have data to store
            if (empty($data['companies'])) {
                if ($command instanceof Command) {
                    $command->warn('No companies fetched from Productive API. Skipping company storage.');
                }
            }
            if (empty($data['projects'])) {
                if ($command instanceof Command) {
                    $command->warn('No projects fetched from Productive API. Skipping project storage.');
                }
            }
            if (empty($data['deals'])) {
                if ($command instanceof Command) {
                    $command->warn('No deals fetched from Productive API. Skipping deal storage.');
                }
            }
            if (empty($data['time_entries'])) {
                if ($command instanceof Command) {
                    $command->warn('No time entries fetched from Productive API. Skipping time entries storage.');
                }
            }

            // Store companies first
            if ($command instanceof Command) {
                $command->info('Storing companies...');
            }

            $companiesSuccess = 0;
            $companiesError = 0;
            foreach ($data['companies'] as $companyData) {
                try {
                    $storeCompanyAction = new StoreCompany();
                    $storeCompanyAction->handle([
                        'companyData' => $companyData,
                        'command' => $command
                    ]);
                    $companiesSuccess++;
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to store company (ID: {$companyData['id']}): " . $e->getMessage());
                    }
                    $companiesError++;
                }
            }
            
            if ($command instanceof Command) {
                $command->info("Companies: {$companiesSuccess} stored successfully, {$companiesError} failed");
            }

            // Then store projects (which depend on companies)
            if ($command instanceof Command) {
                $command->info('Storing projects...');
            }
            
            $projectsWithCompany = 0;
            $projectsSuccess = 0;
            $projectsError = 0;
            foreach ($data['projects'] as $projectData) {
                try {
                    $storeProjectAction = new StoreProject();
                    $storeProjectAction->handle([
                        'projectData' => $projectData,
                        'command' => $command
                    ]);
                    $projectsSuccess++;
                    if (isset($projectData['relationships']['company']['data']['id'])) {
                        $projectsWithCompany++;
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to store project (ID: {$projectData['id']}): " . $e->getMessage());
                    }
                    $projectsError++;
                }
            }
            
            if ($command instanceof Command) {
                $command->info("Projects: {$projectsSuccess} stored successfully, {$projectsError} failed");
                if ($projectsSuccess > 0) {
                    $command->info('Projects with company relationship: ' . $projectsWithCompany . ' (' . round(($projectsWithCompany / max(1, $projectsSuccess)) * 100, 2) . '%)');
                }
            }

            // Finally store deals (which depend on both companies and projects)
            if ($command instanceof Command) {
                $command->info('Storing deals...');
            }
            
            $dealsWithCompany = 0;
            $dealsWithProject = 0;
            $dealsSuccess = 0;
            $dealsError = 0;
            foreach ($data['deals'] as $dealData) {
                try {
                    $storeDealAction = new StoreDeal();
                    $storeDealAction->handle([
                        'dealData' => $dealData,
                        'command' => $command
                    ]);
                    $dealsSuccess++;
                    if (isset($dealData['relationships']['company']['data']['id'])) {
                        $dealsWithCompany++;
                    }
                    if (isset($dealData['relationships']['project']['data']['id'])) {
                        $dealsWithProject++;
                    }
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to store deal (ID: {$dealData['id']}): " . $e->getMessage());
                    }
                    $dealsError++;
                }
            }
            
            if ($command instanceof Command) {
                $command->info("Deals: {$dealsSuccess} stored successfully, {$dealsError} failed");
                if ($dealsSuccess > 0) {
                    $command->info('Deals with company relationship: ' . $dealsWithCompany . ' (' . round(($dealsWithCompany / max(1, $dealsSuccess)) * 100, 2) . '%)');
                    $command->info('Deals with project relationship: ' . $dealsWithProject . ' (' . round(($dealsWithProject / max(1, $dealsSuccess)) * 100, 2) . '%)');
                }
            }

            // Store time entries
            if ($command instanceof Command) {
                $command->info('Storing time entries...');
            }
            
            $timeEntriesSuccess = 0;
            $timeEntriesError = 0;
            foreach ($data['time_entries'] as $timeEntryData) {
                try {
                    $storeTimeEntryAction = new StoreTimeEntry();
                    $storeTimeEntryAction->handle([
                        'timeEntryData' => $timeEntryData,
                        'command' => $command
                    ]);
                    $timeEntriesSuccess++;
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to store time entry (ID: {$timeEntryData['id']}): " . $e->getMessage());
                    }
                    $timeEntriesError++;
                }
            }
            
            if ($command instanceof Command) {
                $command->info("Time entries: {$timeEntriesSuccess} stored successfully, {$timeEntriesError} failed");
            }
            
            // Store time entry versions
            if ($command instanceof Command) {
                $command->info('Storing time entry versions...');
            }
            
            $timeEntryVersionsSuccess = 0;
            $timeEntryVersionsError = 0;
            foreach ($data['time_entry_versions'] as $timeEntryVersionData) {
                try {
                    $storeTimeEntryVersionAction = new StoreTimeEntryVersion();
                    $storeTimeEntryVersionAction->handle([
                        'timeEntryVersionData' => $timeEntryVersionData,
                        'command' => $command
                    ]);
                    $timeEntryVersionsSuccess++;
                } catch (\Exception $e) {
                    if ($command instanceof Command) {
                        $command->error("Failed to store time entry version (ID: {$timeEntryVersionData['id']}): " . $e->getMessage());
                    }
                    $timeEntryVersionsError++;
                }
            }
            
            if ($command instanceof Command) {
                $command->info("Time entry versions: {$timeEntryVersionsSuccess} stored successfully, {$timeEntryVersionsError} failed");
            }

            // Check if we had any errors and warn the user
            $totalErrors = $companiesError + $projectsError + $dealsError + $timeEntriesError + $timeEntryVersionsError;
            if ($totalErrors > 0 && $command instanceof Command) {
                $command->warn("Completed with {$totalErrors} errors. Some records may not have been stored correctly.");
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($command instanceof Command) {
                $command->error('Failed to store data: ' . $e->getMessage());
            }
            return false;
        }
    }
}
