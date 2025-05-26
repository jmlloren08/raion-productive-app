<?php

namespace App\Actions\Productive;

use App\Models\ProductiveCompany;
use Illuminate\Console\Command;

class StoreCompany extends AbstractAction
{
    /**
     * Execute the action to store a company.
     *
     * @param array $parameters
     * @return void
     */
    public function handle(array $parameters = []): void
    {
        $companyData = $parameters['companyData'];
        $command = $parameters['command'] ?? null;
        
        $attributes = $companyData['attributes'] ?? [];
        $relationships = $companyData['relationships'] ?? [];

        // Extract contact information
        $contact = $attributes['contact'] ?? null;

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
            
            if ($command) {
                $command->info("Stored company '{$attributes['name']}' (ID: {$companyData['id']})");
            }
        } catch (\Exception $e) {
            if ($command) {
                $command->error("Failed to store company '{$attributes['name']}' (ID: {$companyData['id']}): " . $e->getMessage());
                // Log additional details for troubleshooting
                $command->warn("Company data: " . json_encode([
                    'id' => $companyData['id'],
                    'name' => $attributes['name'] ?? 'Unknown Company'
                ]));
            } else {
                throw $e;
            }
        }
    }
}
