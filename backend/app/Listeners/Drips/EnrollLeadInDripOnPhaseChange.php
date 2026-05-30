<?php

declare(strict_types=1);

namespace App\Listeners\Drips;

use App\Events\Leads\LeadPhaseChanged;
use App\Services\Drips\DripEnrollmentService;

final class EnrollLeadInDripOnPhaseChange
{
    public function __construct(
        private readonly DripEnrollmentService $drips,
    ) {}

    public function handle(LeadPhaseChanged $event): void
    {
        if ($event->toPhase !== 1) {
            return;
        }

        $this->drips->enroll($event->lead, 'lead_created');
    }
}
