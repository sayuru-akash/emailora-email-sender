<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavedSegmentController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Webhooks\BrevoWebhookController;
use App\Http\Controllers\Webhooks\ResendWebhookController;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/privacy', [PublicPageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PublicPageController::class, 'terms'])->name('terms');
Route::withoutMiddleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
    HandleAppearance::class,
    HandleInertiaRequests::class,
    AddLinkHeadersForPreloadedAssets::class,
])->group(function () {
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/global-search', GlobalSearchController::class)->name('global-search');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/contacts/bulk-action', [ContactController::class, 'bulkAction'])->name('contacts.bulk-action');
    Route::get('/contacts/export', [ContactController::class, 'export'])->name('contacts.export');
    Route::post('/contacts/{contact}/block', [ContactController::class, 'block'])->name('contacts.block');
    Route::post('/contacts/{contact}/unsubscribe', [ContactController::class, 'unsubscribe'])->name('contacts.unsubscribe');
    Route::resource('contacts', ContactController::class);

    Route::resource('tags', TagController::class);
    Route::post('/lists/{list}/add-contacts', [ListController::class, 'addContacts'])->name('lists.add-contacts');
    Route::post('/lists/{list}/remove-contacts', [ListController::class, 'removeContacts'])->name('lists.remove-contacts');
    Route::get('/lists/{list}/export', [ListController::class, 'export'])->name('lists.export');
    Route::resource('lists', ListController::class);

    Route::get('/imports/sample/{format}', [ImportController::class, 'sample'])->whereIn('format', ['csv', 'xlsx'])->name('imports.sample');
    Route::get('/imports/create', [ImportController::class, 'create'])->name('imports.create');
    Route::post('/imports/upload', [ImportController::class, 'upload'])->name('imports.upload');
    Route::get('/imports/{import}/mapping', [ImportController::class, 'mapping'])->name('imports.mapping');
    Route::post('/imports/{import}/preview', [ImportController::class, 'preview'])->name('imports.preview');
    Route::post('/imports/{import}/confirm', [ImportController::class, 'confirm'])->name('imports.confirm');
    Route::get('/imports/{import}/download-failed', [ImportController::class, 'downloadFailed'])->name('imports.download-failed');
    Route::resource('imports', ImportController::class)->only(['index', 'show', 'destroy']);

    Route::post('/templates/{template}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('templates.duplicate');
    Route::get('/templates/{template}/preview', [EmailTemplateController::class, 'preview'])->name('templates.preview');
    Route::resource('templates', EmailTemplateController::class);

    Route::get('/campaigns/builder', [CampaignController::class, 'builder'])->name('campaigns.builder');
    Route::get('/campaigns/audience/contacts', [CampaignController::class, 'audienceContacts'])->name('campaigns.audience.contacts');
    Route::post('/campaigns/audience/estimate', [CampaignController::class, 'audienceEstimate'])->name('campaigns.audience.estimate');
    Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('/campaigns/{campaign}/send-test', [CampaignController::class, 'sendTest'])->name('campaigns.send-test');
    Route::post('/campaigns/{campaign}/resend-failed', [CampaignController::class, 'resendFailed'])->name('campaigns.resend-failed');
    Route::post('/campaigns/{campaign}/recipients/{recipient}/resend', [CampaignController::class, 'resendRecipient'])->name('campaigns.recipients.resend');
    Route::post('/campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
    Route::post('/campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::post('/campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('campaigns.duplicate');
    Route::get('/campaigns/{campaign}/preview', [CampaignController::class, 'preview'])->name('campaigns.preview');
    Route::get('/campaigns/{campaign}/report', [CampaignController::class, 'report'])->name('campaigns.report');
    Route::get('/campaigns/{campaign}/recipients', [CampaignController::class, 'recipients'])->name('campaigns.recipients');
    Route::resource('campaigns', CampaignController::class);

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/campaigns/{campaign}', [ReportController::class, 'campaignReport'])->name('reports.campaign');
    Route::get('/reports/campaigns/{campaign}/export', [ReportController::class, 'exportCampaign'])->name('reports.campaign.export');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [SettingController::class, 'testEmail'])->name('settings.test-email');

    Route::post('/segments/{segment}/preview', [SavedSegmentController::class, 'preview'])->name('segments.preview');
    Route::resource('segments', SavedSegmentController::class);

    Route::middleware('role:owner,admin')->group(function () {
        Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
});

Route::middleware(['auth', 'active', 'role:owner,admin'])->group(function () {
    Route::resource('users', UserController::class);
});

Route::post('/webhooks/email/resend', ResendWebhookController::class)->name('webhooks.email.resend');
Route::post('/webhooks/email/brevo', BrevoWebhookController::class)->name('webhooks.email.brevo');
Route::get('/unsubscribe/{signedToken}', [UnsubscribeController::class, 'show'])->middleware('signed')->name('unsubscribe.show');
Route::post('/unsubscribe/{signedToken}', [UnsubscribeController::class, 'store'])->middleware('signed')->name('unsubscribe.store');

require __DIR__.'/settings.php';
