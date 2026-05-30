<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Leads\LeadPhaseChanged;
use App\Listeners\Drips\EnrollLeadInDripOnPhaseChange;
use App\Listeners\Mail\GuardOutboundMailRecipients;
use App\Listeners\Mail\RecordFilMailDeliveryMetadata;
use App\Listeners\Notifications\DispatchNotificationsOnPhaseChange;
use App\Models\Lead;
use App\Models\User;
use App\Observers\LeadObserver;
use App\Observers\UserObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        LeadPhaseChanged::class => [
            EnrollLeadInDripOnPhaseChange::class,
            DispatchNotificationsOnPhaseChange::class,
        ],
        MessageSent::class => [
            RecordFilMailDeliveryMetadata::class,
        ],
        MessageSending::class => [
            GuardOutboundMailRecipients::class,
        ],
    ];

    public function boot(): void
    {
        Lead::observe(LeadObserver::class);
        User::observe(UserObserver::class);
    }
}
