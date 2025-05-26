<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use Illuminate\Console\Command;

class StoreDeal extends AbstractAction
{
    /**
     * Execute the action to store a deal.
     *
     * @param array $parameters
     * @return void
     */
    public function handle(array $parameters = []): void
    {
        $dealData = $parameters['dealData'];
        $command = $parameters['command'] ?? null;
        
        $companyId = null;
        if (isset($dealData['relationships']['company']['data']['id'])) {
            $companyId = $dealData['relationships']['company']['data']['id'];
            // Check if company exists in our database
            $companyExists = ProductiveCompany::where('id', $companyId)->exists();
            if (!$companyExists) {
                if ($command) {
                    $command->warn("Deal '{$dealData['attributes']['name']}' is linked to company ID: {$companyId}, but this company doesn't exist in our database.");
                }
                $companyId = null; // Reset to avoid foreign key constraint failure
            } else if ($command) {
                $command->info("Deal '{$dealData['attributes']['name']}' is linked to company ID: {$companyId}");
            }
        } else if ($command) {
            $command->warn("Deal '{$dealData['attributes']['name']}' has no company relationship");
        }

        $projectId = null;
        if (isset($dealData['relationships']['project']['data']['id'])) {
            $projectId = $dealData['relationships']['project']['data']['id'];
            // Check if project exists in our database
            $projectExists = ProductiveProject::where('id', $projectId)->exists();
            if (!$projectExists) {
                if ($command) {
                    $command->warn("Deal '{$dealData['attributes']['name']}' is linked to project ID: {$projectId}, but this project doesn't exist in our database.");
                }
                $projectId = null; // Reset to avoid foreign key constraint failure
            } else if ($command) {
                $command->info("Deal '{$dealData['attributes']['name']}' is linked to project ID: {$projectId}");
            }
        } else if ($command) {
            $command->warn("Deal '{$dealData['attributes']['name']}' has no project relationship");
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
            'created_at_api' => $attributes['created_at'] ?? null,
            'paid_at' => $attributes['paid_at'] ?? null,
            'archived_at' => $attributes['archived_at'] ?? null,
            'last_activity_at' => $attributes['last_activity_at'] ?? null,
            
            // Boolean fields
            'sent' => $attributes['sent'] ?? false,
            'verified' => $attributes['verified'] ?? false,
            'sample_data' => $attributes['sample_data'] ?? false,
            'xero_sync' => $attributes['xero_sync'] ?? false,
            'document_type' => $attributes['document_type'] ?? null,
            'lock_version' => $attributes['lock_version'] ?? null,
            
            // Financial info
            'amount' => $attributes['amount'] ?? 0,
            'currency' => $attributes['currency'] ?? null,
            'vat' => $attributes['vat'] ?? null,
            'currency_total' => $attributes['currency_total'] ?? 0,
            'total' => $attributes['total'] ?? 0,
            
            // Status fields
            'status' => $attributes['status'] ?? null,
            'progress' => $attributes['progress'] ?? null,
            
            // JSON fields
            'custom_fields' => is_array($attributes['custom_fields']) 
                ? json_encode($attributes['custom_fields']) 
                : $attributes['custom_fields'] ?? null,
            'line_items' => is_array($attributes['line_items']) 
                ? json_encode($attributes['line_items']) 
                : $attributes['line_items'] ?? null,
            'line_items_attributes' => is_array($attributes['line_items_attributes']) 
                ? json_encode($attributes['line_items_attributes']) 
                : $attributes['line_items_attributes'] ?? null,
            
            // External integration fields
            'jira_keys' => is_array($attributes['jira_keys']) 
                ? json_encode($attributes['jira_keys']) 
                : $attributes['jira_keys'] ?? null,
            'external_id' => $attributes['external_id'] ?? null,
        ];

        try {
            ProductiveDeal::updateOrCreate(
                ['id' => $dealData['id']],
                $data
            );
            
            if ($command) {
                $command->info("Stored deal '{$attributes['name']}' (ID: {$dealData['id']})");
            }
        } catch (\Exception $e) {
            if ($command) {
                $command->error("Failed to store deal '{$attributes['name']}' (ID: {$dealData['id']}): " . $e->getMessage());
                // Log additional details for troubleshooting
                $command->warn("Deal data: " . json_encode([
                    'id' => $dealData['id'],
                    'company_id' => $companyId,
                    'project_id' => $projectId,
                    'name' => $attributes['name'] ?? 'Unknown Deal'
                ]));
            } else {
                throw $e;
            }
        }
    }
}
