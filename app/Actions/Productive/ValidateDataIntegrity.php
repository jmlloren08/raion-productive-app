<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductivePeople;
use App\Models\ProductiveWorkflow;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ValidateDataIntegrity extends AbstractAction
{
    /**
     * Execute the action to validate data integrity.
     *
     * @param array $parameters
     * @return array
     */
    public function handle(array $parameters = []): bool
    {
        $command = $parameters['command'] ?? null;

        try {
            if ($command instanceof Command) {
                $command->info('Validating data integrity...');
            }

            // Company Statistics
            $totalCompanies = ProductiveCompany::count();

            // People Statistics
            $totalPeople = ProductivePeople::count();

            // Workflow Statistics
            $totalWorkflows = ProductiveWorkflow::count();

            // Deal Statistics
            $totalDeals = ProductiveDeal::count();

            // Project Statistics
            $totalProjects = ProductiveProject::count();

            if ($command instanceof Command) {
                $command->info('=== Data Integrity Report ===');

                $command->info('Companies:');
                $command->info("  Total: {$totalCompanies}");

                $command->info('People:');
                $command->info("  Total: {$totalPeople}");

                $command->info('Workflows:');
                $command->info("  Total: {$totalWorkflows}");

                $command->info('Deals:');
                $command->info("  Total: {$totalDeals}");

                $command->info('Projects:');
                $command->info("  Total: {$totalProjects}");

                // Validate relationships
                $this->validateRelationships($command);
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Error validating data integrity: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function validateRelationships(Command $command): void
    {
        // Projects with company relationships
        $projectsWithoutCompany = ProductiveProject::whereDoesntHave('company')->count();
        if ($projectsWithoutCompany > 0) {
            $command->warn("Found {$projectsWithoutCompany} projects without company relationships");
        }

        // Deals with company relationships
        $dealsWithoutCompany = ProductiveDeal::whereDoesntHave('company')->count();
        if ($dealsWithoutCompany > 0) {
            $command->warn("Found {$dealsWithoutCompany} deals without company relationships");
        }

        // Deals with project relationships
        $dealsWithoutProject = ProductiveDeal::whereDoesntHave('project')->count();
        if ($dealsWithoutProject > 0) {
            $command->warn("Found {$dealsWithoutProject} deals without project relationships");
        }

        // Check for any invalid foreign keys
        $this->validateForeignKeys($command);
    }

    private function validateForeignKeys(Command $command): void
    {
        $tables = [
            'productive_projects' => ['company_id' => 'productive_companies'],
            'productive_deals' => [
                'company_id' => 'productive_companies',
                'project_id' => 'productive_projects',
                'creator_id' => 'productive_people',
                'responsible_id' => 'productive_people'
            ]
        ];

        foreach ($tables as $currentTable => $relations) {
            foreach ($relations as $foreignKey => $referencedTable) {
                $invalidCount = DB::table($currentTable)
                    ->whereNotExists(function ($query) use ($referencedTable, $foreignKey, $currentTable) {
                        $query->select(DB::raw(1))
                            ->from($referencedTable)
                            ->whereRaw("{$referencedTable}.id = {$currentTable}.{$foreignKey}");
                    })
                    ->count();

                if ($invalidCount > 0) {
                    $command->warn("Found {$invalidCount} invalid {$foreignKey} references in {$currentTable}");
                }
            }
        }
    }
}
