<?php

namespace App\Console\Commands;

use App\Models\ProductiveProject;
use App\Models\ProductiveCustomField;
use App\Models\ProductiveCfo;
use App\Models\ProductiveCustomFieldValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProjectCustomFieldValues extends Command
{
    protected $signature = 'sync:project-custom-field-values';
    protected $description = 'Sync and resolve custom field values for projects';

    public function handle(): int
    {
        $this->info('Starting project custom field values sync...');
        $startTime = microtime(true);

        try {
            // Get all projects with custom fields
            $projects = ProductiveProject::whereNotNull('custom_fields')->get();
            $this->info("Found {$projects->count()} projects with custom fields");

            $processedCount = 0;
            $errorCount = 0;

            foreach ($projects as $project) {
                try {
                    $this->processProject($project);
                    $processedCount++;
                } catch (\Exception $e) {
                    $this->error("Error processing project {$project->id}: " . $e->getMessage());
                    Log::error("Error processing project {$project->id}: " . $e->getMessage());
                    $errorCount++;
                }
            }

            // Report statistics
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            $this->info('==== Project Custom Field Values Sync Summary ====');
            $this->info("Projects processed: {$processedCount}");
            $this->info("Errors encountered: {$errorCount}");
            $this->info("Execution time: {$executionTime} seconds");
            $this->info('Project custom field values sync completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Project custom field values sync failed: ' . $e->getMessage());
            Log::error('Project custom field values sync error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function processProject(ProductiveProject $project): void
    {
        $customFields = json_decode($project->custom_fields, true) ?? [];
        
        if (empty($customFields)) {
            return;
        }

        DB::beginTransaction();
        try {
            // Delete existing values for this project
            ProductiveCustomFieldValue::where('project_id', $project->id)->delete();

            foreach ($customFields as $fieldId => $value) {
                // Get custom field metadata
                $customField = ProductiveCustomField::find($fieldId);
                if (!$customField) {
                    $this->warn("Custom field {$fieldId} not found for project {$project->id}");
                    continue;
                }

                // Try to find the custom field option
                $customFieldOption = null;
                $resolvedValue = $value;

                // Check if the value is numeric-only
                if (is_numeric($value)) {
                    // Try to find the custom field option
                    $customFieldOption = ProductiveCfo::where('id', $value)->first();
                    
                    if ($customFieldOption) {
                        $resolvedValue = $customFieldOption->name;
                        $this->info("Found custom field option for value {$value}: {$customFieldOption->name}");
                    } else {
                        $this->warn("Custom field option {$value} not found, using as raw value");
                    }
                } else {
                    $this->info("Non-numeric value '{$value}' for field {$fieldId} ({$customField->name}), using as raw value");
                }

                // Create the resolved value record
                ProductiveCustomFieldValue::create([
                    'project_id' => $project->id,
                    'custom_field_id' => $fieldId,
                    'custom_field_option_id' => $customFieldOption?->id,
                    'custom_field_name' => $customField->name,
                    'custom_field_value' => $resolvedValue,
                    'raw_value' => $value
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 