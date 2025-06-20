<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ApprovalPolicyAssignmentController;
use App\Http\Controllers\ApprovalPolicyController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactEntryController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CustomDomainController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\CustomFieldOptionController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DealStatusController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DocumentStyleController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\InvoiceAttributionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LostReasonController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentReminderController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\ProductiveSyncController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PrsController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceTypeController;
use App\Http\Controllers\SubsidiaryController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskListController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\TimeEntryVersionController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowStatusController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Data endpoints
    Route::resource('activities', ActivityController::class)
        ->only(['index', 'show'])
        ->names('activities');
    Route::resource('approval-policies', ApprovalPolicyController::class)
        ->only(['index', 'show'])
        ->names('approval-policies');
    Route::resource('approval-policy-assignments', ApprovalPolicyAssignmentController::class)
        ->only(['index', 'show'])
        ->names('approval-policy-assignments');
    Route::resource('attachments', AttachmentController::class)
        ->only(['index', 'show'])
        ->names('attachments');
    Route::resource('bills', BillController::class)
        ->only(['index', 'show'])
        ->names('bills');
    Route::resource('comments', CommentController::class)
        ->only(['index', 'show'])
        ->names('comments');
    Route::resource('companies', CompanyController::class)
        ->only(['index', 'show'])
        ->names('companies');
    Route::resource('contact-entries', ContactEntryController::class)
        ->only(['index', 'show'])
        ->names('contact-entries');
    Route::resource('contracts', ContractController::class)
        ->only(['index', 'show'])
        ->names('contracts');
    Route::resource('custom-domains', CustomDomainController::class)
        ->only(['index', 'show'])
        ->names('custom-domains');
    Route::resource('custom-field-options', CustomFieldOptionController::class)
        ->only(['index', 'show'])
        ->names('custom-field-options');
    Route::resource('custom-fields', CustomFieldController::class)
        ->only(['index', 'show'])
        ->names('custom-fields');
    Route::resource('deal-statuses', DealStatusController::class)
        ->only(['index', 'show'])
        ->names('deal-statuses');
    Route::resource('deals', DealController::class)
        ->only(['index', 'show'])
        ->names('deals');
    Route::resource('discussions', DiscussionController::class)
        ->only(['index', 'show'])
        ->names('discussions');
    Route::resource('document-styles', DocumentStyleController::class)
        ->only(['index', 'show'])
        ->names('document-styles');
    Route::resource('document-types', DocumentTypeController::class)
        ->only(['index', 'show'])
        ->names('document-types');
    Route::resource('emails', EmailController::class)
        ->only(['index', 'show'])
        ->names('emails');
    Route::resource('events', EventController::class)
        ->only(['index', 'show'])
        ->names('events');
    Route::resource('expenses', ExpenseController::class)
        ->only(['index', 'show'])
        ->names('expenses');
    Route::resource('integrations', IntegrationController::class)
        ->only(['index', 'show'])
        ->names('integrations');
    Route::resource('invoice-attributions', InvoiceAttributionController::class)
        ->only(['index', 'show'])
        ->names('invoice-attributions');
    Route::resource('invoices', InvoiceController::class)
        ->only(['index', 'show'])
        ->names('invoices');
    Route::resource('lost-reasons', LostReasonController::class)
        ->only(['index', 'show'])
        ->names('lost-reasons');
    Route::resource('pages', PageController::class)
        ->only(['index', 'show'])
        ->names('pages');
    Route::resource('projects', ProjectController::class)
        ->only(['index', 'show'])
        ->names('projects');
    Route::resource('payment-reminder-sequences', PrsController::class)
        ->only(['index', 'show'])
        ->names('payment-reminder-sequences');
    Route::resource('payment-reminders', PaymentReminderController::class)
        ->only(['index', 'show'])
        ->names('payment-reminders');
    Route::resource('people', PeopleController::class)
        ->only(['index', 'show'])
        ->names('people');
    Route::resource('pipelines', PipelineController::class)
        ->only(['index', 'show'])
        ->names('pipelines');
    Route::resource('projects', ProjectController::class)
        ->only(['index', 'show'])
        ->names('projects');
    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->only(['index', 'show'])
        ->names('purchase-orders');
    Route::resource('sections', SectionController::class)
        ->only(['index', 'show'])
        ->names('sections');
    Route::resource('service-types', ServiceTypeController::class)
        ->only(['index', 'show'])
        ->names('service-types');
    Route::resource('services', ServiceController::class)
        ->only(['index', 'show'])
        ->names('services');
    Route::resource('subsidiaries', SubsidiaryController::class)
        ->only(['index', 'show'])
        ->names('subsidiaries');
    Route::resource('surveys', SurveyController::class)
        ->only(['index', 'show'])
        ->names('surveys');
    Route::resource('tags', TagController::class)
        ->only(['index', 'show'])
        ->names('tags');
    Route::resource('task-lists', TaskListController::class)
        ->only(['index', 'show'])
        ->names('task-lists');
    Route::resource('tasks', TaskController::class)
        ->only(['index', 'show'])
        ->names('tasks');
    Route::resource('tax-rates', TaxRateController::class)
        ->only(['index', 'show'])
        ->names('tax-rates');
    Route::resource('teams', TeamController::class)
        ->only(['index', 'show'])
        ->names('teams');
    Route::resource('time-entries', TimeEntryController::class)
        ->only(['index', 'show'])
        ->names('time-entries');
    Route::resource('time-entry-versions', TimeEntryVersionController::class)
        ->only(['index', 'show'])
        ->names('time-entry-versions');
    Route::resource('timesheets', TimesheetController::class)
        ->only(['index', 'show'])
        ->names('timesheets');
    Route::resource('todos', TodoController::class)
        ->only(['index', 'show'])
        ->names('todos');
    Route::resource('workflow-statuses', WorkflowStatusController::class)
        ->only(['index', 'show'])
        ->names('workflow-statuses');
    Route::resource('workflows', WorkflowController::class)
        ->only(['index', 'show'])
        ->names('workflows');
    // Sync endpoints
    Route::prefix('productive')->group(function () {
        Route::get('/sync/status', [ProductiveSyncController::class, 'status'])->name('productive.sync.status');
        Route::post('/sync', [ProductiveSyncController::class, 'sync'])->name('productive.sync');
    });

    // Fallback route for Inertia
    Route::fallback(function () {
        return Inertia::render('errors/404', ['status' => 404]);
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
