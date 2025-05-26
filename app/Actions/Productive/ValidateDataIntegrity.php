<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductiveTimeEntries;
use App\Models\ProductiveTimeEntryVersions;
use Illuminate\Console\Command;

class ValidateDataIntegrity extends AbstractAction
{
    /**
     * Execute the action to validate data integrity.
     *
     * @param array $parameters
     * @return array
     */
    public function handle(array $parameters = []): array
    {
        $command = $parameters['command'] ?? null;
        
        if ($command) {
            $command->info('Validating data integrity...');
        }

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
            ],
            'time_entries' => [
                'total' => ProductiveTimeEntries::count(),
                'with_task' => 0,
                'with_service' => 0,
                'with_person' => 0
            ],
            'time_entry_versions' => [
                'total' => ProductiveTimeEntryVersions::count(),
                'with_time_entry' => 0,
                'by_event_type' => []
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

        // Check time entries with relationships
        $timeEntriesWithTask = ProductiveTimeEntries::whereNotNull('task_id')->count();
        $timeEntriesWithService = ProductiveTimeEntries::whereNotNull('service_id')->count();
        $timeEntriesWithPerson = ProductiveTimeEntries::whereNotNull('person_id')->count();

        $stats['time_entries']['with_task'] = $timeEntriesWithTask;
        $stats['time_entries']['with_service'] = $timeEntriesWithService;
        $stats['time_entries']['with_person'] = $timeEntriesWithPerson;

        // Check time entry versions
        $timeEntryVersionsWithTimeEntry = ProductiveTimeEntryVersions::whereNotNull('item_id')->count();
        $stats['time_entry_versions']['with_time_entry'] = $timeEntryVersionsWithTimeEntry;

        // Get counts by event type
        $eventTypeCounts = ProductiveTimeEntryVersions::selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        $stats['time_entry_versions']['by_event_type'] = $eventTypeCounts;

        if ($command) {
            // Log the results
            $command->info('=== Data Integrity Report ===');

            // Companies
            $command->info("Companies: {$stats['companies']['total']} total");
            $command->info("- {$stats['companies']['with_projects']} have related projects (" .
                round(($stats['companies']['with_projects'] / max(1, $stats['companies']['total'])) * 100, 2) . "%)");
            $command->info("- {$stats['companies']['with_deals']} have related deals (" .
                round(($stats['companies']['with_deals'] / max(1, $stats['companies']['total'])) * 100, 2) . "%)");

            // Projects
            $command->info("Projects: {$stats['projects']['total']} total");
            $command->info("- {$stats['projects']['with_company']} have a company (" .
                round(($stats['projects']['with_company'] / max(1, $stats['projects']['total'])) * 100, 2) . "%)");
            $command->info("- {$stats['projects']['with_deals']} have related deals (" .
                round(($stats['projects']['with_deals'] / max(1, $stats['projects']['total'])) * 100, 2) . "%)");
            $command->info("- {$stats['projects']['orphaned']} are orphaned (no company) (" .
                round(($stats['projects']['orphaned'] / max(1, $stats['projects']['total'])) * 100, 2) . "%)");

            // Deals
            $command->info("Deals: {$stats['deals']['total']} total");
            $command->info("- {$stats['deals']['with_company']} have a company (" .
                round(($stats['deals']['with_company'] / max(1, $stats['deals']['total'])) * 100, 2) . "%)");
            $command->info("- {$stats['deals']['with_project']} have a project (" .
                round(($stats['deals']['with_project'] / max(1, $stats['deals']['total'])) * 100, 2) . "%)");
            $command->info("- {$stats['deals']['with_both']} have both company and project (" .
                round(($stats['deals']['with_both'] / max(1, $stats['deals']['total'])) * 100, 2) . "%)");
            $command->info("- {$stats['deals']['orphaned']} are orphaned (no company or project) (" .
                round(($stats['deals']['orphaned'] / max(1, $stats['deals']['total'])) * 100, 2) . "%)");

            // Time Entries
            $command->info("Time Entries: {$stats['time_entries']['total']} total");
            $command->info("- {$stats['time_entries']['with_task']} have a task (" .
                round(($stats['time_entries']['with_task'] / max(1, $stats['time_entries']['total'])) * 100, 2) . "%)");
            $command->info("- {$stats['time_entries']['with_service']} have a service (" .
                round(($stats['time_entries']['with_service'] / max(1, $stats['time_entries']['total'])) * 100, 2) . "%)");
            $command->info("- {$stats['time_entries']['with_person']} have a person (" .
                round(($stats['time_entries']['with_person'] / max(1, $stats['time_entries']['total'])) * 100, 2) . "%)");

            // Time Entry Versions
            $command->info("Time Entry Versions: {$stats['time_entry_versions']['total']} total");
            $command->info("- {$stats['time_entry_versions']['with_time_entry']} linked to time entries (" .
                round(($stats['time_entry_versions']['with_time_entry'] / max(1, $stats['time_entry_versions']['total'])) * 100, 2) . "%)");

            $command->info("Event types: " . json_encode($stats['time_entry_versions']['by_event_type']));
        }

        return $stats;
    }
}
