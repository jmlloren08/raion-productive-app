<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productive_projects', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('productive_companies');
            $table->foreign('project_manager_id')->references('id')->on('productive_people');
            $table->foreign('last_actor_id')->references('id')->on('productive_people');
            $table->foreign('workflow_id')->references('id')->on('productive_workflows');
        });

        Schema::table('productive_time_entries', function (Blueprint $table) {
            $table->foreign('person_id')->references('id')->on('productive_people');
            $table->foreign('service_id')->references('id')->on('productive_services');
            $table->foreign('task_id')->references('id')->on('productive_tasks');
            $table->foreign('deal_id')->references('id')->on('productive_deals');
            $table->foreign('approver_id')->references('id')->on('productive_people');
            $table->foreign('updater_id')->references('id')->on('productive_people');
            $table->foreign('rejecter_id')->references('id')->on('productive_people');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('last_actor_id')->references('id')->on('productive_people');
            $table->foreign('person_subsidiary_id')->references('id')->on('productive_subsidiaries');
            $table->foreign('deal_subsidiary_id')->references('id')->on('productive_subsidiaries');
            $table->foreign('timesheet_id')->references('id')->on('productive_timesheets');
        });

        Schema::table('productive_time_entry_versions', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('productive_people');
        });

        Schema::table('productive_tasks', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('productive_projects');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('assignee_id')->references('id')->on('productive_people');
            $table->foreign('last_actor_id')->references('id')->on('productive_people');
            $table->foreign('task_list_id')->references('id')->on('productive_task_lists');
            $table->foreign('parent_task_id')->references('id')->on('productive_tasks');
            $table->foreign('workflow_status_id')->references('id')->on('productive_workflow_statuses');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });

        Schema::table('productive_people', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('productive_people');
            $table->foreign('company_id')->references('id')->on('productive_companies');
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries');
            $table->foreign('apa_id')->references('id')->on('productive_apas');
            $table->foreign('team_id')->references('id')->on('productive_teams');
        });

        Schema::table('productive_contact_entries', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('productive_companies');
            $table->foreign('person_id')->references('id')->on('productive_people');
            $table->foreign('invoice_id')->references('id')->on('productive_invoices');
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries');
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders');
        });

        Schema::table('productive_subsidiaries', function (Blueprint $table) {
            $table->foreign('contact_entry_id')->references('id')->on('productive_contact_entries');
            $table->foreign('custom_domain_id')->references('id')->on('productive_custom_domains');
            $table->foreign('default_tax_rate_id')->references('id')->on('productive_tax_rates');
            $table->foreign('integration_id')->references('id')->on('productive_integrations');
        });

        Schema::table('productive_services', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('productive_service_types');
            $table->foreign('deal_id')->references('id')->on('productive_deals');
            $table->foreign('person_id')->references('id')->on('productive_people');
            $table->foreign('section_id')->references('id')->on('productive_sections');
        });
        
        Schema::table('productive_bookings', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('productive_services');
            $table->foreign('event_id')->references('id')->on('productive_events');
            $table->foreign('person_id')->references('id')->on('productive_people');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('updater_id')->references('id')->on('productive_people');
            $table->foreign('approver_id')->references('id')->on('productive_people');
            $table->foreign('rejecter_id')->references('id')->on('productive_people');
            $table->foreign('canceler_id')->references('id')->on('productive_people');
            $table->foreign('origin_id')->references('id')->on('productive_bookings');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });
        
        Schema::table('productive_invoices', function (Blueprint $table) {
            $table->foreign('bill_to_id')->references('id')->on('productive_contact_entries');
            $table->foreign('bill_from_id')->references('id')->on('productive_contact_entries');
            $table->foreign('company_id')->references('id')->on('productive_companies');
            $table->foreign('document_type_id')->references('id')->on('productive_document_types');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries');
            $table->foreign('parent_invoice_id')->references('id')->on('productive_invoices');
            $table->foreign('issuer_id')->references('id')->on('productive_people');
            $table->foreign('invoice_attribution_id')->references('id')->on('productive_invoice_attributions');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });
        
        Schema::table('productive_discussions', function (Blueprint $table) {
            $table->foreign('page_id')->references('id')->on('productive_pages');
        });
        
        Schema::table('productive_pages', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('project_id')->references('id')->on('productive_projects');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });
        
        Schema::table('productive_purchase_orders', function (Blueprint $table) {
            $table->foreign('deal_id')->references('id')->on('productive_deals');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('document_type_id')->references('id')->on('productive_document_types');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
            $table->foreign('bill_to_id')->references('id')->on('productive_people');
            $table->foreign('bill_from_id')->references('id')->on('productive_people');
        });
        
        Schema::table('productive_comments', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('productive_companies');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('deal_id')->references('id')->on('productive_deals');
            $table->foreign('discussion_id')->references('id')->on('productive_discussions');
            $table->foreign('invoice_id')->references('id')->on('productive_invoices');
            $table->foreign('person_id')->references('id')->on('productive_people');
            $table->foreign('pinned_by_id')->references('id')->on('productive_people');
            $table->foreign('task_id')->references('id')->on('productive_tasks');
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });
        
        Schema::table('productive_attachments', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('invoice_id')->references('id')->on('productive_invoices');
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders');
            $table->foreign('bill_id')->references('id')->on('productive_bills');
            $table->foreign('email_id')->references('id')->on('productive_emails');
            $table->foreign('page_id')->references('id')->on('productive_pages');
            $table->foreign('expense_id')->references('id')->on('productive_expenses');
            $table->foreign('comment_id')->references('id')->on('productive_comments');
            $table->foreign('task_id')->references('id')->on('productive_tasks');
            $table->foreign('document_style_id')->references('id')->on('productive_document_styles');
            $table->foreign('document_type_id')->references('id')->on('productive_document_types');
            $table->foreign('deal_id')->references('id')->on('productive_deals');
        });
        
        Schema::table('productive_todos', function (Blueprint $table) {
            $table->foreign('assignee_id')->references('id')->on('productive_people');
            $table->foreign('deal_id')->references('id')->on('productive_deals');
            $table->foreign('task_id')->references('id')->on('productive_tasks');
        });

        Schema::table('productive_activities', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('comment_id')->references('id')->on('productive_comments');
            $table->foreign('email_id')->references('id')->on('productive_emails');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });
        
        Schema::table('productive_emails', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('deal_id')->references('id')->on('productive_deals');
            $table->foreign('invoice_id')->references('id')->on('productive_invoices');
            $table->foreign('prs_id')->references('id')->on('productive_prs');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });

        Schema::table('productive_workflow_statuses', function (Blueprint $table) {
            $table->foreign('workflow_id')->references('id')->on('productive_workflows');
        });

        Schema::table('productive_workflows', function (Blueprint $table) {
            $table->foreign('workflow_status_id')->references('id')->on('productive_workflow_statuses');
        });

        Schema::table('productive_boards', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('productive_projects');
        });

        Schema::table('productive_task_lists', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('productive_projects');
            $table->foreign('board_id')->references('id')->on('productive_boards');
        });

        Schema::table('productive_sections', function (Blueprint $table) {
            $table->foreign('deal_id')->references('id')->on('productive_deals');
        });

        Schema::table('productive_document_styles', function (Blueprint $table) {
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });

        Schema::table('productive_document_types', function (Blueprint $table) {
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries');
            $table->foreign('document_style_id')->references('id')->on('productive_document_styles');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });

        Schema::table('productive_invoice_attributions', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('productive_invoices');
            $table->foreign('budget_id')->references('id')->on('productive_deals');
        });

        Schema::table('productive_contracts', function (Blueprint $table) {
            $table->foreign('deal_id')->references('id')->on('productive_deals');
        });

        Schema::table('productive_tax_rates', function (Blueprint $table) {
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries');
        });

        Schema::table('productive_pipelines', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('updater_id')->references('id')->on('productive_people');
        });

        Schema::table('productive_apas', function (Blueprint $table) {
            $table->foreign('person_id')->references('id')->on('productive_people');
            $table->foreign('deal_id')->references('id')->on('productive_deals');
            $table->foreign('approval_policy_id')->references('id')->on('productive_approval_policies');
        });

        Schema::table('productive_deal_statuses', function (Blueprint $table) {
            $table->foreign('pipeline_id')->references('id')->on('productive_pipelines');
        });

        Schema::table('productive_deals', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('company_id')->references('id')->on('productive_companies');
            $table->foreign('document_type_id')->references('id')->on('productive_document_types');
            $table->foreign('responsible_id')->references('id')->on('productive_people');
            $table->foreign('deal_status_id')->references('id')->on('productive_deal_statuses');
            $table->foreign('project_id')->references('id')->on('productive_projects');
            $table->foreign('lost_reason_id')->references('id')->on('productive_lost_reasons');
            $table->foreign('contract_id')->references('id')->on('productive_contracts');
            $table->foreign('contact_id')->references('id')->on('productive_people');
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries');
            $table->foreign('tax_rate_id')->references('id')->on('productive_tax_rates');
            $table->foreign('pipeline_id')->references('id')->on('productive_pipelines');
            $table->foreign('apa_id')->references('id')->on('productive_apas');
        });

        Schema::table('productive_service_types', function (Blueprint $table) {
            $table->foreign('assignee_id')->references('id')->on('productive_people');
        });

        Schema::table('productive_companies', function (Blueprint $table) {
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries');
            $table->foreign('tax_rate_id')->references('id')->on('productive_tax_rates');
        });

        Schema::table('productive_bills', function (Blueprint $table) {
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('deal_id')->references('id')->on('productive_deals');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });

        Schema::table('productive_expenses', function (Blueprint $table) {
            $table->foreign('deal_id')->references('id')->on('productive_deals');
            $table->foreign('service_type_id')->references('id')->on('productive_service_types');
            $table->foreign('person_id')->references('id')->on('productive_people');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('approver_id')->references('id')->on('productive_people');
            $table->foreign('rejecter_id')->references('id')->on('productive_people');
            $table->foreign('service_id')->references('id')->on('productive_services');
            $table->foreign('purchase_order_id')->references('id')->on('productive_purchase_orders');
            $table->foreign('tax_rate_id')->references('id')->on('productive_tax_rates');
            $table->foreign('attachment_id')->references('id')->on('productive_attachments');
        });

        Schema::table('productive_custom_domains', function (Blueprint $table) {
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries');
        });

        Schema::table('productive_timesheets', function (Blueprint $table) {
            $table->foreign('person_id')->references('id')->on('productive_people');
            $table->foreign('creator_id')->references('id')->on('productive_people');
        });

        Schema::table('productive_integrations', function (Blueprint $table) {
            $table->foreign('subsidiary_id')->references('id')->on('productive_subsidiaries');
            $table->foreign('project_id')->references('id')->on('productive_projects');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('deal_id')->references('id')->on('productive_deals');
        });

        Schema::table('productive_payment_reminders', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('updater_id')->references('id')->on('productive_people');
            $table->foreign('invoice_id')->references('id')->on('productive_invoices');
            $table->foreign('prs_id')->references('id')->on('productive_prs');
        });

        Schema::table('productive_prs', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('updater_id')->references('id')->on('productive_people');
            $table->foreign('payment_reminder_id')->references('id')->on('productive_payment_reminders');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_projects', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['project_manager_id']);
            $table->dropForeign(['last_actor_id']);
            $table->dropForeign(['workflow_id']);
        });

        Schema::table('productive_time_entries', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['task_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['approver_id']);
            $table->dropForeign(['updater_id']);
            $table->dropForeign(['rejecter_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['last_actor_id']);
            $table->dropForeign(['person_subsidiary_id']);
            $table->dropForeign(['deal_subsidiary_id']);
            $table->dropForeign(['timesheet_id']);
        });

        Schema::table('productive_time_entry_versions', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
        });

        Schema::table('productive_tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['assignee_id']);
            $table->dropForeign(['last_actor_id']);
            $table->dropForeign(['task_list_id']);
            $table->dropForeign(['parent_task_id']);
            $table->dropForeign(['workflow_status_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_people', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['subsidiary_id']);
            $table->dropForeign(['apa_id']);
            $table->dropForeign(['team_id']);
        });

        Schema::table('productive_contact_entries', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['subsidiary_id']);
            $table->dropForeign(['purchase_order_id']);
        });

        Schema::table('productive_subsidiaries', function (Blueprint $table) {
            $table->dropForeign(['contact_entry_id']);
            $table->dropForeign(['custom_domain_id']);
            $table->dropForeign(['default_tax_rate_id']);
            $table->dropForeign(['integration_id']);
        });

        Schema::table('productive_services', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['section_id']);
        });

        Schema::table('productive_bookings', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropForeign(['event_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['updater_id']);
            $table->dropForeign(['approver_id']);
            $table->dropForeign(['rejecter_id']);
            $table->dropForeign(['canceler_id']);
            $table->dropForeign(['origin_id']);
            $table->dropForeign(['approval_status_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_invoices', function (Blueprint $table) {
            $table->dropForeign(['bill_to_id']);
            $table->dropForeign(['bill_from_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['document_type_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['subsidiary_id']);
            $table->dropForeign(['parent_invoice_id']);
            $table->dropForeign(['issuer_id']);
            $table->dropForeign(['invoice_attribution_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_discussions', function (Blueprint $table) {
            $table->dropForeign(['page_id']);
        });

        Schema::table('productive_pages', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['document_type_id']);
            $table->dropForeign(['attachment_id']);
            $table->dropForeign(['bill_to_id']);
            $table->dropForeign(['bill_from_id']);
        });

        Schema::table('productive_comments', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['discussion_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['pinned_by_id']);
            $table->dropForeign(['task_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_attachments', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['bill_id']);
            $table->dropForeign(['email_id']);
            $table->dropForeign(['page_id']);
            $table->dropForeign(['expense_id']);
            $table->dropForeign(['comment_id']);
            $table->dropForeign(['task_id']);
            $table->dropForeign(['document_style_id']);
            $table->dropForeign(['document_type_id']);
            $table->dropForeign(['deal_id']);
        });

        Schema::table('productive_todos', function (Blueprint $table) {
            $table->dropForeign(['assignee_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['task_id']);
        });

        Schema::table('productive_activities', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['comment_id']);
            $table->dropForeign(['email_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_emails', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['prs_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_workflow_statuses', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
        });

        Schema::table('productive_workflows', function (Blueprint $table) {
            $table->dropForeign(['workflow_status_id']);
        });

        Schema::table('productive_boards', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        Schema::table('productive_task_lists', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['board_id']);
        });

        Schema::table('productive_sections', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
        });

        Schema::table('productive_document_styles', function (Blueprint $table) {
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_document_types', function (Blueprint $table) {
            $table->dropForeign(['subsidiary_id']);
            $table->dropForeign(['document_style_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_invoice_attributions', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['budget_id']);
        });

        Schema::table('productive_contracts', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
        });

        Schema::table('productive_tax_rates', function (Blueprint $table) {
            $table->dropForeign(['subsidiary_id']);
        });

        Schema::table('productive_pipelines', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['updater_id']);
        });

        Schema::table('productive_apas', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['approval_policy_id']);
        });

        Schema::table('productive_deal_statuses', function (Blueprint $table) {
            $table->dropForeign(['pipeline_id']);
        });

        Schema::table('productive_deals', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['document_type_id']);
            $table->dropForeign(['responsible_id']);
            $table->dropForeign(['deal_status_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['lost_reason_id']);
            $table->dropForeign(['contract_id']);
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['subsidiary_id']);
            $table->dropForeign(['tax_rate_id']);
            $table->dropForeign(['pipeline_id']);
            $table->dropForeign(['apa_id']);
        });

        Schema::table('productive_service_types', function (Blueprint $table) {
            $table->dropForeign(['assignee_id']);
        });

        Schema::table('productive_companies', function (Blueprint $table) {
            $table->dropForeign(['subsidiary_id']);
            $table->dropForeign(['tax_rate_id']);
        });

        Schema::table('productive_bills', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_expenses', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
            $table->dropForeign(['service_type_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['approver_id']);
            $table->dropForeign(['rejecter_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['tax_rate_id']);
            $table->dropForeign(['attachment_id']);
        });

        Schema::table('productive_custom_domains', function (Blueprint $table) {
            $table->dropForeign(['subsidiary_id']);
        });

        Schema::table('productive_timesheets', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['creator_id']);
        });

        Schema::table('productive_integrations', function (Blueprint $table) {
            $table->dropForeign(['subsidiary_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['deal_id']);
        });

        Schema::table('productive_payment_reminders', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['updater_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['prs_id']);
        });

        Schema::table('productive_prs', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['updater_id']);
            $table->dropForeign(['payment_reminder_id']);
        });
    }
};
