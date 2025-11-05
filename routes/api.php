<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Http\Controllers\Api\V1\ChartController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentGatewayConnectionController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectMemberController;
use App\Http\Controllers\Api\V1\Public\ReportController as PublicReportController;
use App\Http\Controllers\Api\V1\PushSubscriptionController;
use App\Http\Controllers\Api\V1\RecurringInvoiceScheduleController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TimeEntryController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserMembershipController;
use App\Http\Controllers\Api\V1\UserPrivacySettingController;
use App\Http\Controllers\Api\V1\UserTimeEntryController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->name('v1.')->group(static function (): void {
    Route::middleware([
        'auth:api',
        'verified',
    ])->group(static function (): void {
        // Organization routes
        Route::name('organizations.')->group(static function (): void {
            Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('show');
            Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('update');
        });

        // Member routes
        Route::name('members.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/members', [MemberController::class, 'index'])->name('index');
            Route::put('/members/{member}', [MemberController::class, 'update'])->name('update');
            Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('destroy');
            Route::post('/members/{member}/invite-placeholder', [MemberController::class, 'invitePlaceholder'])->name('invite-placeholder');
            Route::post('/members/{member}/make-placeholder', [MemberController::class, 'makePlaceholder'])->name('make-placeholder');
            Route::post('member/{member}/merge-into', [MemberController::class, 'mergeInto'])->name('merge-into');
        });

        // User routes
        Route::name('users.')->group(static function (): void {
            Route::get('/users/me', [UserController::class, 'me'])->name('me');
        });

        // User Privacy Setting routes
        Route::name('user-privacy-settings.')->group(static function (): void {
            Route::get('/users/me/privacy-settings', [UserPrivacySettingController::class, 'show'])->name('show');
            Route::put('/users/me/privacy-settings', [UserPrivacySettingController::class, 'update'])->name('update');
            Route::get('/users/me/privacy-settings/consent-history', [UserPrivacySettingController::class, 'consentHistory'])->name('consent-history');
            Route::get('/users/me/privacy-settings/data-collection-status', [UserPrivacySettingController::class, 'dataCollectionStatus'])->name('data-collection-status');
        });

        // Api token routes
        Route::name('api-tokens.')->group(static function (): void {
            Route::get('/users/me/api-tokens', [ApiTokenController::class, 'index'])->name('index');
            Route::post('/users/me/api-tokens', [ApiTokenController::class, 'store'])->name('store');
            Route::post('/users/me/api-tokens/{apiToken}/revoke', [ApiTokenController::class, 'revoke'])->name('revoke');
            Route::delete('/users/me/api-tokens/{apiToken}', [ApiTokenController::class, 'destroy'])->name('destroy');
        });

        // Push subscription routes
        Route::name('push-subscriptions.')->group(static function (): void {
            Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('store');
            Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('destroy');
            Route::get('/vapid-public-key', [PushSubscriptionController::class, 'vapidPublicKey'])->name('vapid-public-key');
        });

        // User Member routes
        Route::name('users.memberships.')->group(static function (): void {
            Route::get('/users/me/memberships', [UserMembershipController::class, 'myMemberships'])->name('my-memberships');
        });

        // Invitation routes
        Route::name('invitations.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/invitations', [InvitationController::class, 'index'])->name('index');
            Route::post('/invitations', [InvitationController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('resend')->middleware('check-organization-blocked');
            Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('destroy')->middleware('check-organization-blocked');
        });

        // Project routes
        Route::name('projects.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/projects', [ProjectController::class, 'index'])->name('index');
            Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('show');
            Route::post('/projects', [ProjectController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        });

        // Project member routes
        Route::name('project-members.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/projects/{project}/project-members', [ProjectMemberController::class, 'index'])->name('index');
            Route::post('/projects/{project}/project-members', [ProjectMemberController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/project-members/{projectMember}', [ProjectMemberController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/project-members/{projectMember}', [ProjectMemberController::class, 'destroy'])->name('destroy');
        });

        // Time entry routes
        Route::name('time-entries.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/time-entries', [TimeEntryController::class, 'index'])->name('index');
            Route::get('/time-entries/export', [TimeEntryController::class, 'indexExport'])->name('index-export');
            Route::get('/time-entries/aggregate', [TimeEntryController::class, 'aggregate'])->name('aggregate');
            Route::get('/time-entries/aggregate/export', [TimeEntryController::class, 'aggregateExport'])->name('aggregate-export');
            Route::post('/time-entries', [TimeEntryController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/time-entries/{timeEntry}', [TimeEntryController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::patch('/time-entries', [TimeEntryController::class, 'updateMultiple'])->name('update-multiple')->middleware('check-organization-blocked');
            Route::delete('/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy'])->name('destroy');
            Route::delete('/time-entries', [TimeEntryController::class, 'destroyMultiple'])->name('destroy-multiple');
        });

        Route::name('users.time-entries.')->group(static function (): void {
            Route::get('/users/me/time-entries/active', [UserTimeEntryController::class, 'myActive'])->name('my-active');
            Route::get('/users/me/time-entries', [UserTimeEntryController::class, 'my'])->name('my'); // TODO
        });

        // Report routes
        Route::name('reports.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/reports', [ReportController::class, 'index'])->name('index');
            Route::get('/reports/{report}', [ReportController::class, 'show'])->name('show');
            Route::post('/reports', [ReportController::class, 'store'])->name('store');
            Route::put('/reports/{report}', [ReportController::class, 'update'])->name('update');
            Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('destroy');
        });

        // Chart routes
        Route::name('charts.')->prefix('/organizations/{organization}/charts')->group(static function (): void {
            Route::get('/weekly-project-overview', [ChartController::class, 'weeklyProjectOverview'])->name('weekly-project-overview');
            Route::get('/latest-tasks', [ChartController::class, 'latestTasks'])->name('latest-tasks');
            Route::get('/last-seven-days', [ChartController::class, 'lastSevenDays'])->name('last-seven-days');
            Route::get('/latest-team-activity', [ChartController::class, 'latestTeamActivity'])->name('latest-team-activity');
            Route::get('/daily-tracked-hours', [ChartController::class, 'dailyTrackedHours'])->name('daily-tracked-hours');
            Route::get('/total-weekly-time', [ChartController::class, 'totalWeeklyTime'])->name('total-weekly-time');
            Route::get('/total-weekly-billable-time', [ChartController::class, 'totalWeeklyBillableTime'])->name('total-weekly-billable-time');
            Route::get('/total-weekly-billable-amount', [ChartController::class, 'totalWeeklyBillableAmount'])->name('total-weekly-billable-amount');
            Route::get('/weekly-history', [ChartController::class, 'weeklyHistory'])->name('weekly-history');
        });

        // Tag routes
        Route::name('tags.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/tags', [TagController::class, 'index'])->name('index');
            Route::post('/tags', [TagController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/tags/{tag}', [TagController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('destroy');
        });

        // Client routes
        Route::name('clients.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/clients', [ClientController::class, 'index'])->name('index');
            Route::post('/clients', [ClientController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/clients/{client}', [ClientController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('destroy');
        });

        // Task routes
        Route::name('tasks.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/tasks', [TaskController::class, 'index'])->name('index');
            Route::post('/tasks', [TaskController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('destroy');
        });

        // Import routes
        Route::name('import.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/importers', [ImportController::class, 'index'])->name('index');
            Route::post('/import', [ImportController::class, 'import'])->name('import')->middleware('check-organization-blocked');
        });

        // Export routes
        Route::name('export.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::post('/export', [ExportController::class, 'export'])->name('export');
        });

        // Payment Gateway Connection routes (user-scoped)
        Route::name('payment-gateways.')->group(static function (): void {
            Route::get('/payment-gateways', [PaymentGatewayConnectionController::class, 'index'])->name('index');
            Route::post('/payment-gateways/authorization-url', [PaymentGatewayConnectionController::class, 'getAuthorizationUrl'])->name('authorization-url');
            Route::post('/payment-gateways/callback', [PaymentGatewayConnectionController::class, 'handleCallback'])->name('callback');
            Route::delete('/payment-gateways/{connection}', [PaymentGatewayConnectionController::class, 'destroy'])->name('destroy');
        });

        // Invoice routes (organization-scoped)
        Route::name('invoices.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('index');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('show');
            Route::post('/invoices', [InvoiceController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
            Route::post('/invoices/{invoice}/mark-as-sent', [InvoiceController::class, 'markAsSent'])->name('mark-as-sent')->middleware('check-organization-blocked');
            Route::post('/invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('mark-as-paid')->middleware('check-organization-blocked');
        });

        // Payment routes (organization-scoped)
        Route::name('payments.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/payments', [PaymentController::class, 'index'])->name('index');
            Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('show');
            Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('refund')->middleware('check-organization-blocked');
        });

        // Recurring Invoice Schedule routes (organization-scoped)
        Route::name('recurring-schedules.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/recurring-schedules', [RecurringInvoiceScheduleController::class, 'index'])->name('index');
            Route::get('/recurring-schedules/{schedule}', [RecurringInvoiceScheduleController::class, 'show'])->name('show');
            Route::post('/recurring-schedules', [RecurringInvoiceScheduleController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/recurring-schedules/{schedule}', [RecurringInvoiceScheduleController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/recurring-schedules/{schedule}', [RecurringInvoiceScheduleController::class, 'destroy'])->name('destroy');
            Route::post('/recurring-schedules/{schedule}/pause', [RecurringInvoiceScheduleController::class, 'pause'])->name('pause')->middleware('check-organization-blocked');
            Route::post('/recurring-schedules/{schedule}/resume', [RecurringInvoiceScheduleController::class, 'resume'])->name('resume')->middleware('check-organization-blocked');
        });
    });

    Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index');

    // Public routes
    Route::name('public.')->prefix('/public')->group(static function (): void {
        Route::get('/reports', [PublicReportController::class, 'show'])->name('reports.show');
    });
});

// Webhook routes (outside v1 prefix, no authentication)
Route::prefix('webhooks')->name('webhooks.')->group(static function (): void {
    Route::post('/stripe', [\App\Http\Controllers\Webhooks\StripeWebhookController::class, 'handle'])->name('stripe');
    Route::post('/paypal', [\App\Http\Controllers\Webhooks\PayPalWebhookController::class, 'handle'])->name('paypal');
});

/**
 * Fallback routes, to prevent a rendered HTML page in /api/* routes
 * The / route is also included since the fallback is not triggered on the root route
 */
Route::get('/', function (): void {
    throw new NotFoundHttpException('API resource not found');
});
Route::fallback(function (): void {
    throw new NotFoundHttpException('API resource not found');
});
