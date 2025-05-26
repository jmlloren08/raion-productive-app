<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use Illuminate\Console\Command;

class StoreProject extends AbstractAction
{
    /**
     * Execute the action to store a project.
     *
     * @param array $parameters
     * @return void
     */
    public function handle(array $parameters = []): void
    {
        $projectData = $parameters['projectData'];
        $command = $parameters['command'] ?? null;
        
        $companyId = null;
        if (isset($projectData['relationships']['company']['data']['id'])) {
            $companyId = $projectData['relationships']['company']['data']['id'];
            // Check if company exists in our database
            $companyExists = ProductiveCompany::where('id', $companyId)->exists();
            if (!$companyExists) {
                if ($command) {
                    $command->warn("Project '{$projectData['attributes']['name']}' is linked to company ID: {$companyId}, but this company doesn't exist in our database.");
                }
                $companyId = null; // Reset to avoid foreign key constraint failure
            } else if ($command) {
                $command->info("Project '{$projectData['attributes']['name']}' is linked to company ID: {$companyId}");
            }
        } else if ($command) {
            $command->warn("Project '{$projectData['attributes']['name']}' has no company relationship");
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
            
            if ($command) {
                $command->info("Stored project '{$attributes['name']}' (ID: {$projectData['id']})");
            }
        } catch (\Exception $e) {
            if ($command) {
                $command->error("Failed to store project '{$attributes['name']}' (ID: {$projectData['id']}): " . $e->getMessage());
                // Log additional details for troubleshooting
                $command->warn("Project data: " . json_encode([
                    'id' => $projectData['id'],
                    'company_id' => $companyId,
                    'name' => $attributes['name'] ?? 'Unknown Project'
                ]));
            } else {
                throw $e;
            }
        }
    }
}
