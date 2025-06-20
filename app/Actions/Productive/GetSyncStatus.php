<?php

namespace App\Actions\Productive;

use App\Models\ProductiveApa;
use App\Models\ProductiveCompany;
use App\Models\ProductiveProject;
use App\Models\ProductiveDeal;
use App\Models\ProductiveTimeEntry;
use App\Models\ProductiveTimeEntryVersion;
use App\Models\ProductiveDocumentType;
use App\Models\ProductiveDocumentStyle;
use App\Models\ProductivePeople;
use App\Models\ProductiveTaxRate;
use App\Models\ProductiveSubsidiary;
use App\Models\ProductiveWorkflow;
use App\Models\ProductiveContactEntry;
use App\Models\ProductiveLostReason;
use App\Models\ProductiveDealStatus;
use App\Models\ProductiveContract;
use App\Models\ProductivePurchaseOrder;
use App\Models\ProductiveApprovalPolicy;
use App\Models\ProductivePipeline;
use App\Models\ProductiveAttachment;
use App\Models\ProductiveBill;
use App\Models\ProductiveTeam;
use App\Models\ProductiveEmail;
use App\Models\ProductiveInvoice;
use App\Models\ProductiveInvoiceAttribution;
use App\Models\ProductiveActivity;
use App\Models\ProductiveBoard;
use App\Models\ProductiveBooking;
use App\Models\ProductiveCfo;
use App\Models\ProductiveComment;
use App\Models\ProductiveCustomDomain;
use App\Models\ProductiveCustomField;
use App\Models\ProductiveDiscussion;
use App\Models\ProductiveEvent;
use App\Models\ProductiveExpense;
use App\Models\ProductiveIntegration;
use App\Models\ProductivePage;
use App\Models\ProductivePaymentReminder;
use App\Models\ProductivePrs;
use App\Models\ProductiveSection;
use App\Models\ProductiveServiceType;
use App\Models\ProductiveTag;
use App\Models\ProductiveTask;
use App\Models\ProductiveTaskList;
use App\Models\ProductiveTimesheet;
use App\Models\ProductiveTodo;
use App\Models\ProductiveWorkflowStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GetSyncStatus extends AbstractAction
{
    public function __construct(
        private GetRelationshipStats $getRelationshipStatsAction
    ) {}

    /**
     * Execute the action to get sync status.
     *
     * @param array $parameters
     * @return array
     */
    public function handle(array $parameters = []): array
    {
        $command = $parameters['command'] ?? null;

        try {
            $lastSync = Cache::get('productive_last_sync');
            $isCurrentlySyncing = Cache::get('productive_is_syncing', false);

            $stats = [
                'companies_count' => ProductiveCompany::count(),
                'projects_count' => ProductiveProject::count(),
                'people_count' => ProductivePeople::count(),
                'workflows_count' => ProductiveWorkflow::count(),
                'document_types_count' => ProductiveDocumentType::count(),
                'subsidiaries_count' => ProductiveSubsidiary::count(),
                'tax_rates_count' => ProductiveTaxRate::count(),
                'document_styles_count' => ProductiveDocumentStyle::count(),
                'pipelines_count' => ProductivePipeline::count(),
                'deal_statuses_count' => ProductiveDealStatus::count(),
                'lost_reasons_count' => ProductiveLostReason::count(),
                'contracts_count' => ProductiveContract::count(),
                'purchase_orders_count' => ProductivePurchaseOrder::count(),
                'contact_entries_count' => ProductiveContactEntry::count(),
                'approval_policies_count' => ProductiveApprovalPolicy::count(),
                'apas_count' => ProductiveApa::count(),
                'deals_count' => ProductiveDeal::count(),
                'emails_count' => ProductiveEmail::count(),
                'bills_count' => ProductiveBill::count(),
                'attachments_count' => ProductiveAttachment::count(),
                'teams_count' => ProductiveTeam::count(),
                'invoices_count' => ProductiveInvoice::count(),
                'invoice_attributions_count' => ProductiveInvoiceAttribution::count(),
                'boards_count' => ProductiveBoard::count(),
                'bookings_count' => ProductiveBooking::count(),
                'comments_count' => ProductiveComment::count(),
                'discussions_count' => ProductiveDiscussion::count(),
                'events_count' => ProductiveEvent::count(),
                'expenses_count' => ProductiveExpense::count(),
                'integrations_count' => ProductiveIntegration::count(),
                'pages_count' => ProductivePage::count(),
                'sections_count' => ProductiveSection::count(),
                'custom_domains_count' => ProductiveCustomDomain::count(),
                'timesheet_count' => ProductiveTimesheet::count(),
                'workflow_status_count' => ProductiveWorkflowStatus::count(),
                'tags_count' => ProductiveTag::count(),
                'service_types_count' => ProductiveServiceType::count(),
                'service_count' => ProductiveServiceType::count(),
                'task_lists_count' => ProductiveTaskList::count(),
                'task_count' => ProductiveTask::count(),
                'todos_count' => ProductiveTodo::count(),
                'prs_count' => ProductivePrs::count(),
                'payment_reminders_count' => ProductivePaymentReminder::count(),
                'custom_fields_count' => ProductiveCustomField::count(),
                'custom_field_options_count' => ProductiveCfo::count(),
                'time_entries_count' => ProductiveTimeEntry::count(),
                'time_entry_versions_count' => ProductiveTimeEntryVersion::count(),
            ];

            // Get detailed relationship stats
            $relationshipStats = $this->getRelationshipStatsAction->handle();

            if ($command instanceof Command) {
                $command->info('Sync Status Report:');
                $command->info('=================');
                foreach ($stats as $key => $value) {
                    $command->info("- {$key}: {$value}");
                }
            }

            return [
                'last_sync' => $lastSync,
                'is_syncing' => $isCurrentlySyncing,
                'stats' => $stats,
                'relationships' => $relationshipStats
            ];
        } catch (\Exception $e) {
            if ($command instanceof Command) {
                $command->error('Error getting sync status: ' . $e->getMessage());
            }
            Log::error('Error getting sync status: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return [];
        }
    }
}
