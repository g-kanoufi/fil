<?php

declare(strict_types=1);

namespace App\Listeners\Notifications;

use App\Events\Leads\LeadPhaseChanged;
use App\Services\Notifications\NotificationDispatcher;

final class DispatchNotificationsOnPhaseChange
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function handle(LeadPhaseChanged $event): void
    {
        $this->notifications->dispatch('lead.phase_changed', $event->lead, [
            'from_phase' => $event->fromPhase,
            'to_phase' => $event->toPhase,
            'changed_fields' => ['pipeline_phase'],
        ], $event->actor);
    }
}
