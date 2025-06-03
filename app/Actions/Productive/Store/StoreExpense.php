<?php

namespace App\Actions\Productive\Store;

use App\Actions\Productive\AbstractAction;
use App\Models\ProductiveExpense;
use App\Models\ProductiveCompany;
use App\Models\ProductivePeople;
use App\Models\ProductiveProject;
use App\Models\ProductiveTask;
use App\Models\ProductiveDeal;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveService;
use App\Models\ProductiveServiceType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreExpense extends AbstractAction
{
    /**
     * Required fields that must be present in the expense data
     */
    protected array $requiredFields = [
        'name',
        'date',
    ];

    /**
     * Foreign key relationships to validate
     */
    protected array $foreignKeys = [
        'deal_id' => ProductiveDeal::class,
        'service_type_id' => ProductiveServiceType::class,
        'person_id' => ProductivePeople::class,
        'creator_id' => ProductivePeople::class,
        'approver_id' => ProductivePeople::class,
        'rejecter_id' => ProductivePeople::class,
        'service_id' => ProductiveService::class,
        'purchase_order_id' => ProductiveExpense::class,
        'tax_rate_id' => ProductiveExpense::class,
        'attachment_id' => ProductiveAttachment::class,
    ];

    /**
     * Store an expense in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $expenseData = $parameters['expenseData'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$expenseData) {
            throw new \Exception('Expense data is required');
        }

        try {
            if ($command instanceof Command) {
                $command->info("Processing expense: {$expenseData['id']}");
            }

            // Validate basic data structure
            if (!isset($expenseData['id'])) {
                throw new \Exception("Missing required field 'id' in root data object");
            }

            $attributes = $expenseData['attributes'] ?? [];
            $relationships = $expenseData['relationships'] ?? [];

            // Add type from root level if not in attributes
            if (!isset($attributes['type']) && isset($expenseData['type'])) {
                $attributes['type'] = $expenseData['type'];
            }

            // Validate required fields
            $this->validateRequiredFields($attributes, $expenseData['id'], $command);

            // Prepare base data
            $data = [
                'id' => $expenseData['id'],
                'type' => $attributes['type'] ?? $expenseData['type'] ?? 'expenses',
            ];

            // Add all attributes with safe fallbacks
            foreach ($attributes as $key => $value) {
                $data[$key] = $value;
            }

            // Handle foreign key relationships
            $this->handleForeignKeys($relationships, $data, $attributes['name'] ?? 'Unknown Expense', $command);

            // Validate data types
            $this->validateDataTypes($data);

            // Create or update expense
            ProductiveExpense::updateOrCreate(
                ['id' => $expenseData['id']],
                $data
            );

            if ($command instanceof Command) {
                $command->info("Successfully stored expense: {$attributes['name']} (ID: {$expenseData['id']})");
            }

            return true;
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error("Failed to store expense {$expenseData['id']}: " . $e->getMessage());
            }
            Log::error("Failed to store expense {$expenseData['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that all required fields are present
     *
     * @param array $attributes
     * @param string $expenseId
     * @param Command|null $command
     * @throws \Exception
     */
    protected function validateRequiredFields(array $attributes, string $expenseId, ?Command $command): void
    {
        // Check for missing required fields
        $missingFields = [];
        foreach ($this->requiredFields as $field) {
            if (!isset($attributes[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $message = "Required fields missing for expense {$expenseId}: " . implode(', ', $missingFields);
            if ($command) {
                $command->error($message);
            }
            throw new \Exception($message);
        }
    }

    /**
     * Handle foreign key relationships
     *
     * @param array $relationships
     * @param array &$data
     * @param string $expenseTitle
     * @param Command|null $command
     */
    protected function handleForeignKeys(array $relationships, array &$data, string $expenseTitle, ?Command $command): void
    {
        // Map relationship keys to their corresponding data keys
        $relationshipMap = [
            'deal' => 'deal_id',
            'service_type' => 'service_type_id',
            'person' => 'person_id',
            'creator' => 'creator_id',
            'rejecter' => 'rejecter_id',
            'approver' => 'approver_id',
            'service' => 'service_id',
            'purchase_order' => 'purchase_order_id',
            'tax_rate' => 'tax_rate_id',
            'attachment' => 'attachment_id',
        ];

        foreach ($relationshipMap as $apiKey => $dbKey) {
            if (isset($relationships[$apiKey]['data']['id'])) {
                $id = $relationships[$apiKey]['data']['id'];
                if ($command) {
                    $command->info("Processing relationship {$apiKey} with ID: {$id}");
                }

                // Get the model class for this relationship
                $modelClass = $this->foreignKeys[$dbKey];

                if (!$modelClass::where('id', $id)->exists()) {
                    if ($command) {
                        $command->warn("Expense '{$expenseTitle}' is linked to {$apiKey}: {$id}, but this record doesn't exist in our database.");
                    }
                    $data[$dbKey] = null;
                } else {
                    $data[$dbKey] = $id;
                    if ($command) {
                        $command->info("Successfully linked {$apiKey} with ID: {$id}");
                    }
                }
            } else {
                if ($command) {
                    $command->info("No relationship data found for {$apiKey}");
                }
            }
        }
    }

    /**
     * Validate data types for all fields
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateDataTypes(array $data): void
    {
        $rules = [
            'name' => 'required|string',
            'date' => 'required|date',
            'pay_on' => 'nullable|date',
            'paid_on' => 'nullable|date',
            'position' => 'nullable|integer',
            'invoiced' => 'required|boolean',
            'approved' => 'required|boolean',
            'approved_at' => 'nullable|date',
            'rejected' => 'required|boolean',
            'rejected_reason' => 'nullable|string',
            'rejected_at' => 'nullable|date',
            'deleted_at_api' => 'nullable|date',
            'reimbursable' => 'required|boolean',
            'reimbursed_on' => 'nullable|date',
            'exchange_rate' => 'nullable|numeric',
            'exchange_rate_normalized' => 'nullable|numeric',
            'exchange_date' => 'nullable|date',
            'created_at_api' => 'nullable|date',
            'quantity' => 'nullable|numeric',
            'quantity_received' => 'nullable|numeric',
            'custom_fields' => 'nullable|array',
            'draft' => 'required|boolean',
            'exported' => 'required|boolean',
            'exported_at' => 'nullable|date',
            'export_integration_type_id' => 'nullable|integer',
            'export_id' => 'nullable|integer',
            'export_url' => 'nullable|string',
            'company_reference_id' => 'nullable|integer',
            'external_payment_id' => 'nullable|string',
            'currency' => 'required|string',
            'currency_default' => 'required|string',
            'currency_normalized' => 'required|string',
            'amount' => 'nullable|numeric',
            'amount_default' => 'nullable|numeric',
            'amount_normalized' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'total_amount_default' => 'nullable|numeric',
            'total_amount_normalized' => 'nullable|numeric',
            'billable_amount' => 'nullable|numeric',
            'billable_amount_default' => 'nullable|numeric',
            'billable_amount_normalized' => 'nullable|numeric',
            'profit' => 'nullable|numeric',
            'profit_default' => 'nullable|numeric',
            'profit_normalized' => 'nullable|numeric',
            'recognized_revenue' => 'nullable|numeric',
            'recognized_revenue_default' => 'nullable|numeric',
            'recognized_revenue_normalized' => 'nullable|numeric',
            'amount_with_tax' => 'nullable|numeric',
            'amount_with_tax_default' => 'nullable|numeric',
            'amount_with_tax_normalized' => 'nullable|numeric',
            'total_amount_with_tax' => 'nullable|numeric',
            'total_amount_with_tax_default' => 'nullable|numeric',
            'total_amount_with_tax_normalized' => 'nullable|numeric',
            // Relationships
            'deal_id' => 'nullable|string',
            'service_type_id' => 'nullable|string',
            'person_id' => 'nullable|string',
            'creator_id' => 'nullable|string',
            'approver_id' => 'nullable|string',
            'rejecter_id' => 'nullable|string',
            'vendor_id' => 'nullable|string',
            'service_id' => 'nullable|string',
            'purchase_order_id' => 'nullable|string',
            'tax_rate_id' => 'nullable|string',
            'attachment_id' => 'nullable|string',
            // Custom fields
            'custom_field_people' => 'nullable|array',
            'custom_field_attachments' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
