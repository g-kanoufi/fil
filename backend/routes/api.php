<?php

use App\Http\Controllers\Api\Public\V1\BrandingController;
use App\Http\Controllers\Api\Public\V1\FormConfigController;
use App\Http\Controllers\Api\Public\V1\LeadIntakeController;
use App\Http\Controllers\Api\Portal\V1\PortalSessionController;
use App\Http\Controllers\Api\Portal\V1\ProspectApplicationController;
use App\Http\Controllers\Api\Portal\V1\ProspectFddController;
use App\Http\Controllers\Api\V1\AchCustomerController;
use App\Http\Controllers\Api\V1\AchTransferController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\ActivityPageViewController;
use App\Http\Controllers\Api\V1\AiStreamController;
use App\Http\Controllers\Api\V1\AiThreadController;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\AreaController;
use App\Http\Controllers\Api\V1\ClosingController;
use App\Http\Controllers\Api\V1\CommunicationController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DocumentDownloadController;
use App\Http\Controllers\Api\V1\EntityNoteController;
use App\Http\Controllers\Api\V1\DocumentsBrowserController;
use App\Http\Controllers\Api\V1\DocumentsExportController;
use App\Http\Controllers\Api\V1\DripCampaignController;
use App\Http\Controllers\Api\V1\FddAvailabilityController;
use App\Http\Controllers\Api\V1\FddBulkSendController;
use App\Http\Controllers\Api\V1\FddController;
use App\Http\Controllers\Api\V1\FddDeliveryController;
use App\Http\Controllers\Api\V1\FddSignatureController;
use App\Http\Controllers\Api\V1\FieldController;
use App\Http\Controllers\Api\V1\FieldGroupController;
use App\Http\Controllers\Api\V1\FieldReorderController;
use App\Http\Controllers\Api\V1\FieldSchemaController;
use App\Http\Controllers\Api\V1\GridQueryController;
use App\Http\Controllers\Api\V1\GridQueryInterpretController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\GeographyController;
use App\Http\Controllers\Api\V1\InterestRegionController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\MailSettingsController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\NotificationRuleController;
use App\Http\Controllers\Api\V1\OptionsController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PosConnectionController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RelatableEntityController;
use App\Http\Controllers\Api\V1\RoyaltyController;
use App\Http\Controllers\Api\V1\RoyaltyIntelligenceController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\StaffTodoController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\StoreOpeningChecklistController;
use App\Http\Controllers\Api\V1\StoreOwnerController;
use App\Http\Controllers\Api\V1\WidgetFormController;
use App\Http\Controllers\Api\V1\WidgetFormFieldSyncController;
use App\Http\Controllers\Api\V1\WidgetFormRotateSiteKeyController;
use App\Http\Controllers\Webhooks\DwollaWebhookController;
use App\Http\Controllers\Webhooks\ESignWebhookController;
use App\Http\Controllers\Webhooks\MailgunInboundWebhookController;
use App\Http\Controllers\Webhooks\MailgunWebhookController;
use App\Http\Controllers\Webhooks\PlaidWebhookController;
use App\Http\Controllers\Webhooks\TwilioWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('api.health');

Route::prefix('v1')->group(function (): void {
    Route::post('/session', [SessionController::class, 'store'])
        ->middleware('throttle.staff_login')
        ->name('api.v1.session.store');

    Route::middleware(['auth:sanctum', 'staff'])->group(function (): void {
        Route::get('/session', [SessionController::class, 'show'])->name('api.v1.session.show');
        Route::delete('/session', [SessionController::class, 'destroy'])->name('api.v1.session.destroy');
        Route::get('/profile', [ProfileController::class, 'show'])->name('api.v1.profile.show');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('api.v1.profile.update');
        Route::get('/dashboard', DashboardController::class)->name('api.v1.dashboard');
        Route::get('/activity', [ActivityController::class, 'index'])->name('api.v1.activity.index');
        Route::post('/activity/page-views', [ActivityPageViewController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('api.v1.activity.page-views.store');
        Route::get('/activity/export', [ActivityController::class, 'export'])->name('api.v1.activity.export');
        Route::get('/activity/subjects/{type}/{id}', [ActivityController::class, 'forSubject'])
            ->name('api.v1.activity.subjects');
        Route::get('/app-config', AppConfigController::class)->name('api.v1.app-config');
        Route::get('/options', OptionsController::class)->name('api.v1.options');
        Route::get('/fields', [FieldSchemaController::class, 'index'])->name('api.v1.fields.index');
        Route::get('/field-groups', [FieldGroupController::class, 'index'])->name('api.v1.field-groups.index');
        Route::post('/field-groups', [FieldGroupController::class, 'store'])->name('api.v1.field-groups.store');
        Route::patch('/field-groups/{fieldGroup}', [FieldGroupController::class, 'update'])
            ->name('api.v1.field-groups.update');
        Route::delete('/field-groups/{fieldGroup}', [FieldGroupController::class, 'destroy'])
            ->name('api.v1.field-groups.destroy');
        Route::get('/fields/relatable-entities', RelatableEntityController::class)
            ->name('api.v1.fields.relatable-entities');
        Route::post('/fields/reorder', FieldReorderController::class)->name('api.v1.fields.reorder');
        Route::post('/fields', [FieldController::class, 'store'])->name('api.v1.fields.store');
        Route::patch('/fields/{field}', [FieldController::class, 'update'])->name('api.v1.fields.update');
        Route::delete('/fields/{field}', [FieldController::class, 'destroy'])->name('api.v1.fields.destroy');
        Route::get('/widget-forms', [WidgetFormController::class, 'index'])->name('api.v1.widget-forms.index');
        Route::post('/widget-forms', [WidgetFormController::class, 'store'])->name('api.v1.widget-forms.store');
        Route::get('/widget-forms/{widgetForm}', [WidgetFormController::class, 'show'])
            ->name('api.v1.widget-forms.show');
        Route::patch('/widget-forms/{widgetForm}', [WidgetFormController::class, 'update'])
            ->name('api.v1.widget-forms.update');
        Route::put('/widget-forms/{widgetForm}/fields', WidgetFormFieldSyncController::class)
            ->name('api.v1.widget-forms.fields.sync');
        Route::post('/widget-forms/{widgetForm}/rotate-site-key', WidgetFormRotateSiteKeyController::class)
            ->name('api.v1.widget-forms.rotate-site-key');
        Route::get('/leads', [LeadController::class, 'index'])->name('api.v1.leads.index');
        Route::post('/leads', [LeadController::class, 'store'])->name('api.v1.leads.store');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('api.v1.leads.show');
        Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('api.v1.leads.update');
        Route::post('/leads/{lead}/transition-phase', [LeadController::class, 'transitionPhase'])
            ->name('api.v1.leads.transition-phase');
        Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])
            ->name('api.v1.leads.convert');
        Route::get('/leads/{lead}/notes', [EntityNoteController::class, 'indexForLead'])
            ->name('api.v1.leads.notes.index');
        Route::post('/leads/{lead}/notes', [EntityNoteController::class, 'storeForLead'])
            ->name('api.v1.leads.notes.store');
        Route::get('/fdds/summary', [FddController::class, 'summary'])->name('api.v1.fdds.summary');
        Route::get('/fdds', [FddController::class, 'index'])->name('api.v1.fdds.index');
        Route::post('/fdds', [FddController::class, 'store'])->name('api.v1.fdds.store');
        Route::post('/fdds/bulk-send', [FddBulkSendController::class, 'store'])->name('api.v1.fdds.bulk-send');
        Route::get('/fdds/{fdd}', [FddController::class, 'show'])->name('api.v1.fdds.show');
        Route::patch('/fdds/{fdd}', [FddController::class, 'update'])->name('api.v1.fdds.update');
        Route::post('/fdds/{fdd}/leads/{lead}/send', [FddController::class, 'sendToLead'])
            ->name('api.v1.fdds.send');
        Route::get('/fdd-deliveries', [FddDeliveryController::class, 'index'])->name('api.v1.fdd-deliveries.index');
        Route::get('/leads/{lead}/fdd-deliveries', [FddDeliveryController::class, 'indexForLead'])
            ->name('api.v1.leads.fdd-deliveries.index');
        Route::get('/leads/{lead}/fdd-availability', [FddAvailabilityController::class, 'forLead'])
            ->name('api.v1.leads.fdd-availability');
        Route::get('/areas/{area}/fdd-availability', [FddAvailabilityController::class, 'forArea'])
            ->name('api.v1.areas.fdd-availability');
        Route::get('/fdd-deliveries/{fddDelivery}', [FddDeliveryController::class, 'show'])
            ->name('api.v1.fdd-deliveries.show');
        Route::post('/fdd-deliveries/{fddDelivery}/resend', [FddDeliveryController::class, 'resend'])
            ->name('api.v1.fdd-deliveries.resend');
        Route::post('/fdd-deliveries/{fddDelivery}/sign', [FddSignatureController::class, 'store'])
            ->name('api.v1.fdd-deliveries.sign');
        Route::get('/documents/settings', [DocumentsBrowserController::class, 'settings'])
            ->name('api.v1.documents.settings');
        Route::get('/documents/rows', [DocumentsBrowserController::class, 'rows'])
            ->name('api.v1.documents.rows');
        Route::post('/documents/export/start', [DocumentsExportController::class, 'start'])
            ->name('api.v1.documents.export.start');
        Route::get('/documents/export/status', [DocumentsExportController::class, 'status'])
            ->name('api.v1.documents.export.status');
        Route::get('/documents', [DocumentController::class, 'index'])->name('api.v1.documents.index');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('api.v1.documents.show');
        Route::get('/documents/{document}/download', DocumentDownloadController::class)
            ->name('api.v1.documents.download');
        Route::get('/closings/export', [ClosingController::class, 'export'])->name('api.v1.closings.export');
        Route::get('/closings', [ClosingController::class, 'index'])->name('api.v1.closings.index');
        Route::get('/closings/{closing}', [ClosingController::class, 'show'])->name('api.v1.closings.show');
        Route::patch('/closings/{closing}', [ClosingController::class, 'update'])->name('api.v1.closings.update');
        Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('api.v1.contacts.show');
        Route::patch('/contacts/{contact}', [ContactController::class, 'update'])->name('api.v1.contacts.update');
        Route::get('/contacts/{contact}/notes', [EntityNoteController::class, 'indexForContact'])
            ->name('api.v1.contacts.notes.index');
        Route::post('/contacts/{contact}/notes', [EntityNoteController::class, 'storeForContact'])
            ->name('api.v1.contacts.notes.store');
        Route::get('/stores', [StoreController::class, 'index'])->name('api.v1.stores.index');
        Route::post('/stores', [StoreController::class, 'store'])->name('api.v1.stores.store');
        Route::get('/stores/{store}', [StoreController::class, 'show'])->name('api.v1.stores.show');
        Route::patch('/stores/{store}', [StoreController::class, 'update'])->name('api.v1.stores.update');
        Route::get('/stores/{store}/opening-checklist', [StoreOpeningChecklistController::class, 'show'])
            ->name('api.v1.stores.opening-checklist.show');
        Route::patch('/stores/{store}/opening-checklist', [StoreOpeningChecklistController::class, 'update'])
            ->name('api.v1.stores.opening-checklist.update');
        Route::get('/stores/{store}/notes', [EntityNoteController::class, 'indexForStore'])
            ->name('api.v1.stores.notes.index');
        Route::post('/stores/{store}/notes', [EntityNoteController::class, 'storeForStore'])
            ->name('api.v1.stores.notes.store');
        Route::get('/todos', [StaffTodoController::class, 'index'])->name('api.v1.todos.index');
        Route::post('/todos', [StaffTodoController::class, 'store'])->name('api.v1.todos.store');
        Route::patch('/todos/{staffTodo}', [StaffTodoController::class, 'update'])->name('api.v1.todos.update');
        Route::delete('/todos/{staffTodo}', [StaffTodoController::class, 'destroy'])->name('api.v1.todos.destroy');
        Route::patch('/entity-notes/{entityNote}', [EntityNoteController::class, 'update'])
            ->name('api.v1.entity-notes.update');
        Route::delete('/entity-notes/{entityNote}', [EntityNoteController::class, 'destroy'])
            ->name('api.v1.entity-notes.destroy');
        Route::get('/stores/{store}/owners', [StoreOwnerController::class, 'index'])
            ->name('api.v1.stores.owners.index');
        Route::put('/stores/{store}/owners', [StoreOwnerController::class, 'sync'])
            ->name('api.v1.stores.owners.sync');
        Route::get('/stores/{store}/royalty-periods', [RoyaltyController::class, 'indexForStore'])
            ->name('api.v1.stores.royalty-periods.index');
        Route::post('/stores/{store}/royalty-periods/calculate', [RoyaltyController::class, 'calculate'])
            ->name('api.v1.stores.royalty-periods.calculate');
        Route::post('/stores/{store}/royalty-periods/{royaltyPeriod}/trigger-ach', [RoyaltyController::class, 'triggerAch'])
            ->name('api.v1.stores.royalty-periods.trigger-ach');
        Route::get('/royalty-periods/{royaltyPeriod}', [RoyaltyController::class, 'show'])
            ->name('api.v1.royalty-periods.show');
        Route::get('/royalties/intelligence', RoyaltyIntelligenceController::class)
            ->name('api.v1.royalties.intelligence');
        Route::get('/ach-transfers/reconciliation', [AchTransferController::class, 'reconciliation'])
            ->name('api.v1.ach-transfers.reconciliation');
        Route::get('/ach-transfers', [AchTransferController::class, 'index'])->name('api.v1.ach-transfers.index');
        Route::get('/ach-transfers/{achTransfer}', [AchTransferController::class, 'show'])
            ->name('api.v1.ach-transfers.show');
        Route::get('/stores/{store}/ach/customer', [AchCustomerController::class, 'show'])
            ->name('api.v1.stores.ach.customer');
        Route::post('/stores/{store}/ach/enroll', [AchCustomerController::class, 'enroll'])
            ->name('api.v1.stores.ach.enroll');
        Route::post('/stores/{store}/ach/dwolla/client-token', [AchCustomerController::class, 'dwollaClientToken'])
            ->name('api.v1.stores.ach.dwolla-client-token');
        Route::post('/stores/{store}/ach/dwolla/certify-ownership', [AchCustomerController::class, 'certifyOwnership'])
            ->name('api.v1.stores.ach.dwolla-certify-ownership');
        Route::post('/stores/{store}/ach/plaid/link-token', [AchCustomerController::class, 'linkToken'])
            ->name('api.v1.stores.ach.plaid-link-token');
        Route::post('/stores/{store}/ach/plaid/complete', [AchCustomerController::class, 'completePlaidLink'])
            ->name('api.v1.stores.ach.plaid-complete');
        Route::get('/stores/{store}/pos-connections', [PosConnectionController::class, 'indexForStore'])
            ->name('api.v1.stores.pos-connections.index');
        Route::post('/stores/{store}/pos-connections/{posConnection}/sync', [PosConnectionController::class, 'sync'])
            ->name('api.v1.stores.pos-connections.sync');
        Route::get('/communications', [CommunicationController::class, 'index'])->name('api.v1.communications.index');
        Route::post('/communications', [CommunicationController::class, 'store'])->name('api.v1.communications.store');
        Route::get('/notifications/profile', [NotificationPreferenceController::class, 'index'])
            ->name('api.v1.notifications.profile');
        Route::put('/notifications/profile', [NotificationPreferenceController::class, 'update'])
            ->name('api.v1.notifications.profile.update');
        Route::get('/notifications/rules/schema', [NotificationRuleController::class, 'schema'])
            ->name('api.v1.notifications.rules.schema');
        Route::get('/notifications/rules', [NotificationRuleController::class, 'index'])
            ->name('api.v1.notifications.rules.index');
        Route::get('/notifications/rules/{notificationRule}', [NotificationRuleController::class, 'show'])
            ->name('api.v1.notifications.rules.show');
        Route::patch('/notifications/rules/{notificationRule}', [NotificationRuleController::class, 'update'])
            ->name('api.v1.notifications.rules.update');
        Route::get('/settings/mail', [MailSettingsController::class, 'show'])
            ->name('api.v1.settings.mail.show');
        Route::post('/settings/mail/verify', [MailSettingsController::class, 'verify'])
            ->name('api.v1.settings.mail.verify');
        Route::post('/settings/mail/test', [MailSettingsController::class, 'sendTest'])
            ->name('api.v1.settings.mail.test');
        Route::get('/drip-campaigns/schema', [DripCampaignController::class, 'schema'])
            ->name('api.v1.drip-campaigns.schema');
        Route::get('/drip-campaigns', [DripCampaignController::class, 'index'])
            ->name('api.v1.drip-campaigns.index');
        Route::post('/drip-campaigns', [DripCampaignController::class, 'store'])
            ->name('api.v1.drip-campaigns.store');
        Route::get('/drip-campaigns/{dripCampaign}', [DripCampaignController::class, 'show'])
            ->name('api.v1.drip-campaigns.show');
        Route::patch('/drip-campaigns/{dripCampaign}', [DripCampaignController::class, 'update'])
            ->name('api.v1.drip-campaigns.update');
        Route::delete('/drip-campaigns/{dripCampaign}', [DripCampaignController::class, 'destroy'])
            ->name('api.v1.drip-campaigns.destroy');
        Route::get('/areas', [AreaController::class, 'index'])->name('api.v1.areas.index');
        Route::post('/areas', [AreaController::class, 'store'])->name('api.v1.areas.store');
        Route::get('/areas/{area}', [AreaController::class, 'show'])->name('api.v1.areas.show');
        Route::patch('/areas/{area}', [AreaController::class, 'update'])->name('api.v1.areas.update');
        Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->name('api.v1.areas.destroy');
        Route::get('/geography/north-america', [GeographyController::class, 'northAmerica'])
            ->name('api.v1.geography.north-america');
        Route::get('/interest-regions', [InterestRegionController::class, 'index'])
            ->name('api.v1.interest-regions.index');
        Route::post('/interest-regions', [InterestRegionController::class, 'store'])
            ->name('api.v1.interest-regions.store');
        Route::patch('/interest-regions/{interestRegion}', [InterestRegionController::class, 'update'])
            ->name('api.v1.interest-regions.update');
        Route::delete('/interest-regions/{interestRegion}', [InterestRegionController::class, 'destroy'])
            ->name('api.v1.interest-regions.destroy');
        Route::post('/interest-regions/sync-defaults', [InterestRegionController::class, 'syncDefaults'])
            ->name('api.v1.interest-regions.sync-defaults');
        Route::get('/organizations', [OrganizationController::class, 'index'])->name('api.v1.organizations.index');
        Route::post('/query/{resource}', GridQueryController::class)->name('api.v1.query');
        Route::post('/query/{resource}/interpret', GridQueryInterpretController::class)
            ->name('api.v1.query.interpret');
        Route::get('/ai/threads', [AiThreadController::class, 'index'])->name('api.v1.ai.threads.index');
        Route::post('/ai/threads', [AiThreadController::class, 'store'])->name('api.v1.ai.threads.store');
        Route::get('/ai/threads/{aiThread}', [AiThreadController::class, 'show'])->name('api.v1.ai.threads.show');
        Route::post('/ai/threads/{aiThread}/messages', [AiThreadController::class, 'sendMessage'])
            ->name('api.v1.ai.threads.messages');
        Route::post('/ai/threads/{aiThread}/stream', [AiStreamController::class, 'stream'])
            ->name('api.v1.ai.threads.stream');
    });
});

Route::post('/webhooks/dwolla', DwollaWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('api.webhooks.dwolla');
Route::post('/webhooks/plaid', PlaidWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('api.webhooks.plaid');
Route::post('/webhooks/mailgun', MailgunWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('api.webhooks.mailgun');
Route::post('/webhooks/mailgun/inbound', MailgunInboundWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('api.webhooks.mailgun.inbound');
Route::post('/webhooks/esign', ESignWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('api.webhooks.esign');

Route::prefix('public/v1')->middleware(['throttle:60,1'])->group(function (): void {
    Route::get('/branding', BrandingController::class)->name('api.public.v1.branding');
});

Route::prefix('public/v1')->middleware(['throttle:60,1', 'throttle.embed_lead_intake', 'embed.site_key', 'embed.origin'])->group(function (): void {
    Route::get('/form-config', FormConfigController::class)->name('api.public.v1.form-config');
    Route::post('/leads', [LeadIntakeController::class, 'store'])->name('api.public.v1.leads.store');
});

Route::prefix('portal/v1')->middleware(['throttle:60,1'])->group(function (): void {
    Route::post('/session', [PortalSessionController::class, 'store'])->name('api.portal.v1.session.store');
    Route::post('/setup-password', [PortalSessionController::class, 'setupPassword'])
        ->name('api.portal.v1.setup-password');

    Route::middleware(['auth:sanctum', 'prospect.portal'])->group(function (): void {
        Route::get('/session', [PortalSessionController::class, 'show'])->name('api.portal.v1.session.show');
        Route::delete('/session', [PortalSessionController::class, 'destroy'])->name('api.portal.v1.session.destroy');
        Route::get('/application', [ProspectApplicationController::class, 'show'])
            ->name('api.portal.v1.application.show');
        Route::patch('/application', [ProspectApplicationController::class, 'update'])
            ->name('api.portal.v1.application.update');
        Route::get('/fdd-deliveries', [ProspectFddController::class, 'index'])
            ->name('api.portal.v1.fdd-deliveries.index');
        Route::post('/fdd-deliveries/{fddDelivery}/sign-session', [ProspectFddController::class, 'signSession'])
            ->name('api.portal.v1.fdd-deliveries.sign-session');
        Route::post('/fdd-deliveries/{fddDelivery}/sign', [ProspectFddController::class, 'sign'])
            ->name('api.portal.v1.fdd-deliveries.sign');
    });
});

Route::prefix('webhooks')->middleware('throttle:120,1')->group(function (): void {
    Route::post('/twilio/inbound', [TwilioWebhookController::class, 'inbound'])
        ->name('webhooks.twilio.inbound');
    Route::post('/twilio/status', [TwilioWebhookController::class, 'status'])
        ->name('webhooks.twilio.status');
});
