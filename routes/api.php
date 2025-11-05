<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ActivitySnapshotController;
use App\Http\Controllers\Api\V1\ApiKeyController;
use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Http\Controllers\Api\V1\ChartController;
use App\Http\Controllers\Api\V1\FocusSessionController;
use App\Http\Controllers\Api\V1\TeamFocusAnalyticsController;
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
use App\Http\Controllers\Api\V1\PayrollController;
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
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\WebhookDeliveryController;
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
        'throttle.api:free', // Rate limiting - default to 'free' tier (100 req/min)
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

        // API Keys routes (n8n integration)
        Route::name('api-keys.')->group(static function (): void {
            Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('index');
            Route::get('/api-keys/scopes', [ApiKeyController::class, 'scopes'])->name('scopes');
            Route::get('/api-keys/{apiKey}', [ApiKeyController::class, 'show'])->name('show');
            Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('store');
            Route::put('/api-keys/{apiKey}', [ApiKeyController::class, 'update'])->name('update');
            Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('destroy');
        });

        // Webhook routes (n8n integration)
        Route::name('webhooks.')->group(static function (): void {
            Route::get('/webhooks', [WebhookController::class, 'index'])->name('index');
            Route::get('/webhooks/events', [WebhookController::class, 'events'])->name('events');
            Route::get('/webhooks/{webhook}', [WebhookController::class, 'show'])->name('show');
            Route::post('/webhooks', [WebhookController::class, 'store'])->name('store');
            Route::put('/webhooks/{webhook}', [WebhookController::class, 'update'])->name('update');
            Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
            Route::post('/webhooks/{webhook}/test', [WebhookController::class, 'test'])->name('test');
        });

        // Webhook Delivery routes (n8n integration)
        Route::name('webhook-deliveries.')->group(static function (): void {
            Route::get('/webhooks/{webhook}/deliveries', [WebhookDeliveryController::class, 'index'])->name('index');
            Route::get('/webhooks/{webhook}/deliveries/{delivery}', [WebhookDeliveryController::class, 'show'])->name('show');
            Route::post('/webhooks/{webhook}/deliveries/{delivery}/retry', [WebhookDeliveryController::class, 'retry'])->name('retry');
        });

        // Activity Snapshots routes (desktop app)
        Route::name('activity-snapshots.')->group(static function (): void {
            Route::post('/activity-snapshots', [ActivitySnapshotController::class, 'store'])->name('store');
            Route::get('/activity-snapshots/daily-summary', [ActivitySnapshotController::class, 'dailySummary'])->name('daily-summary');
            Route::get('/activity-snapshots/weekly-summary', [ActivitySnapshotController::class, 'weeklySummary'])->name('weekly-summary');
            Route::get('/activity-snapshots/hourly', [ActivitySnapshotController::class, 'hourly'])->name('hourly');
            Route::get('/activity-snapshots/focus-sessions', [ActivitySnapshotController::class, 'focusSessions'])->name('focus-sessions');
            Route::get('/activity-snapshots/timeline', [ActivitySnapshotController::class, 'timeline'])->name('timeline');
            Route::delete('/activity-snapshots', [ActivitySnapshotController::class, 'destroy'])->name('destroy');
        });

        // Focus Sessions routes (analytics)
        Route::name('focus-sessions.')->group(static function (): void {
            Route::get('/focus-sessions', [FocusSessionController::class, 'index'])->name('index');
            Route::get('/focus-sessions/stats', [FocusSessionController::class, 'stats'])->name('stats');
            Route::get('/focus-sessions/heatmap', [FocusSessionController::class, 'heatmap'])->name('heatmap');
            Route::get('/focus-sessions/streaks', [FocusSessionController::class, 'streaks'])->name('streaks');
            Route::get('/focus-sessions/daily-summary', [FocusSessionController::class, 'dailySummary'])->name('daily-summary');
            Route::get('/focus-sessions/productive-hours', [FocusSessionController::class, 'productiveHours'])->name('productive-hours');
            Route::post('/focus-sessions/detect', [FocusSessionController::class, 'detect'])->name('detect');
            Route::get('/focus-sessions/{id}', [FocusSessionController::class, 'show'])->name('show');
            Route::delete('/focus-sessions/{id}', [FocusSessionController::class, 'destroy'])->name('destroy');
            Route::delete('/focus-sessions', [FocusSessionController::class, 'destroyRange'])->name('destroy-range');
        });

        // Team Focus Analytics routes (organization-level)
        Route::name('team-analytics.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/team-analytics/stats', [TeamFocusAnalyticsController::class, 'teamStats'])->name('stats');
            Route::get('/team-analytics/rankings', [TeamFocusAnalyticsController::class, 'memberRankings'])->name('rankings');
            Route::get('/team-analytics/heatmap', [TeamFocusAnalyticsController::class, 'teamHeatmap'])->name('heatmap');
            Route::get('/team-analytics/productive-hours', [TeamFocusAnalyticsController::class, 'teamProductiveHours'])->name('productive-hours');
            Route::get('/team-analytics/distribution', [TeamFocusAnalyticsController::class, 'teamFocusDistribution'])->name('distribution');
            Route::get('/team-analytics/insights', [TeamFocusAnalyticsController::class, 'teamInsights'])->name('insights');
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

        // Payroll routes (organization-scoped)
        Route::name('payrolls.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/payrolls', [PayrollController::class, 'index'])->name('index');
            Route::get('/payrolls/summary', [PayrollController::class, 'summary'])->name('summary');
            Route::get('/payrolls/{payroll}', [PayrollController::class, 'show'])->name('show');
            Route::post('/payrolls', [PayrollController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::post('/payrolls/{payroll}/approve', [PayrollController::class, 'approve'])->name('approve')->middleware('check-organization-blocked');
            Route::post('/payrolls/{payroll}/mark-as-paid', [PayrollController::class, 'markAsPaid'])->name('mark-as-paid')->middleware('check-organization-blocked');
            Route::delete('/payrolls/{payroll}', [PayrollController::class, 'destroy'])->name('destroy');
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
