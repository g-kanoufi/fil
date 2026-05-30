<?php

declare(strict_types=1);

namespace App\Events\Leads;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LeadPhaseChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly int $fromPhase,
        public readonly int $toPhase,
        public readonly ?User $actor,
    ) {}
}
