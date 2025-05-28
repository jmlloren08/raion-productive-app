<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StoreData extends AbstractAction
{
    public function __construct(
        private StoreCompany $storeCompanyAction,
        private StoreProject $storeProjectAction,
        private StorePeople $storePeopleAction,
        private StoreWorkflow $storeWorkflowAction,
        private StoreDeal $storeDealAction
    ) {}

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
            if (empty($data['companies']) && empty($data['projects']) && 
                empty($data['people']) && empty($data['workflows']) &&
                empty($data['deals'])) {
                if ($command instanceof Command) {
                    $command->warn('No data fetched from Productive API. Skipping storage.');
                }
                return true;
            }

            // Store companies
            if (!empty($data['companies'])) {
                if ($command instanceof Command) {
                    $command->info('Storing companies...');
                }

                $companiesSuccess = 0;
                $companiesError = 0;
                foreach ($data['companies'] as $companyData) {
                    try {
                        $this->storeCompanyAction->handle([
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
            }

            // Store people
            if (!empty($data['people'])) {
                if ($command instanceof Command) {
                    $command->info('Storing people...');
                }

                $peopleSuccess = 0;
                $peopleError = 0;
                foreach ($data['people'] as $personData) {
                    try {
                        $this->storePeopleAction->handle([
                            'personData' => $personData,
                            'command' => $command
                        ]);
                        $peopleSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store person (ID: {$personData['id']}): " . $e->getMessage());
                        }
                        $peopleError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("People: {$peopleSuccess} stored successfully, {$peopleError} failed");
                }
            }

            // Store workflows
            if (!empty($data['workflows'])) {
                if ($command instanceof Command) {
                    $command->info('Storing workflows...');
                }

                $workflowsSuccess = 0;
                $workflowsError = 0;
                foreach ($data['workflows'] as $workflowData) {
                    try {
                        $this->storeWorkflowAction->handle([
                            'workflowData' => $workflowData,
                            'command' => $command
                        ]);
                        $workflowsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store workflow (ID: {$workflowData['id']}): " . $e->getMessage());
                        }
                        $workflowsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Workflows: {$workflowsSuccess} stored successfully, {$workflowsError} failed");
                }
            }

            // Store deals
            if (!empty($data['deals'])) {
                if ($command instanceof Command) {
                    $command->info('Storing deals...');
                }

                $dealsSuccess = 0;
                $dealsError = 0;
                foreach ($data['deals'] as $dealData) {
                    try {
                        $this->storeDealAction->handle([
                            'dealData' => $dealData,
                            'command' => $command
                        ]);
                        $dealsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store deal (ID: {$dealData['id']}): " . $e->getMessage());
                        }
                        $dealsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Deals: {$dealsSuccess} stored successfully, {$dealsError} failed");
                }
            }

            // Store projects
            if (!empty($data['projects'])) {
                if ($command instanceof Command) {
                    $command->info('Storing projects...');
                }

                $projectsSuccess = 0;
                $projectsError = 0;
                foreach ($data['projects'] as $projectData) {
                    try {
                        $this->storeProjectAction->handle([
                            'projectData' => $projectData,
                            'command' => $command
                        ]);
                        $projectsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store project (ID: {$projectData['id']}): " . $e->getMessage());
                        }
                        $projectsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Projects: {$projectsSuccess} stored successfully, {$projectsError} failed");
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($command instanceof Command) {
                $command->error('Error storing data: ' . $e->getMessage());
            }
            return false;
        }
    }
}
