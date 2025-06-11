<?php

namespace App\Console\Commands;

use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductiveCustomField;
use App\Models\ProductiveCfo;
use App\Models\ProductiveCustomFieldValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCustomFieldValues extends Command
{
    protected $signature = 'sync:custom-field-values';
    protected $description = 'Sync and resolve custom field values for projects and deals';

    public function handle(): int
    {
        $this->info('Starting custom field values sync...');
        $startTime = microtime(true);

        try {
            // Process projects
            $this->info('Processing projects...');
            $projects = ProductiveProject::whereNotNull('custom_fields')->get();
            $this->info("Found {$projects->count()} projects with custom fields");
            $this->processProjects($projects);

            // Process deals
            $this->info('Processing deals...');
            $deals = ProductiveDeal::whereNotNull('custom_fields')->get();
            $this->info("Found {$deals->count()} deals with custom fields");
            $this->processDeals($deals);

            // Report statistics
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            $this->info('==== Custom Field Values Sync Summary ====');
            $this->info("Execution time: {$executionTime} seconds");
            $this->info('Custom field values sync completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Custom field values sync failed: ' . $e->getMessage());
            Log::error('Custom field values sync error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function processProjects($projects): void
    {
        $processedCount = 0;
        $errorCount = 0;

        foreach ($projects as $project) {
            try {
                $this->processEntity($project, 'project');
                $processedCount++;
            } catch (\Exception $e) {
                $this->error("Error processing project {$project->id}: " . $e->getMessage());
                Log::error("Error processing project {$project->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("Projects: {$processedCount} processed, {$errorCount} errors");
    }

    protected function processDeals($deals): void
    {
        $processedCount = 0;
        $errorCount = 0;

        foreach ($deals as $deal) {
            try {
                $this->processEntity($deal, 'deal');
                $processedCount++;
            } catch (\Exception $e) {
                $this->error("Error processing deal {$deal->id}: " . $e->getMessage());
                Log::error("Error processing deal {$deal->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("Deals: {$processedCount} processed, {$errorCount} errors");
    }

    protected function processEntity($entity, string $type): void
    {
        $customFields = json_decode($entity->custom_fields, true) ?? [];
        
        if (empty($customFields)) {
            return;
        }

        DB::beginTransaction();
        try {
            // Delete existing values for this entity
            ProductiveCustomFieldValue::where('entity_id', $entity->id)
                ->where('entity_type', $type)
                ->delete();

            foreach ($customFields as $fieldId => $value) {
                // Get custom field metadata
                $customField = ProductiveCustomField::find($fieldId);
                if (!$customField) {
                    $this->warn("Custom field {$fieldId} not found for {$type} {$entity->id}");
                    continue;
                }

                // Handle array values
                if (is_array($value)) {
                    $value = json_encode($value);
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
                    'entity_id' => $entity->id,
                    'entity_type' => $type,
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