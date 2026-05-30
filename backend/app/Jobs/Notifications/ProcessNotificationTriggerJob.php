<?php

declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Models\Lead;
use App\Models\User;
use App\Services\Notifications\NotificationProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessNotificationTriggerJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $triggerSlug,
        public readonly ?int $leadId,
        public readonly array $context = [],
        public readonly ?int $actorUserId = null,
        public readonly ?int $userId = null,
    ) {}

    public function handle(NotificationProcessor $processor): void
    {
        $lead = $this->leadId !== null
            ? Lead::query()->with(['owner', 'prospect', 'area'])->find($this->leadId)
            : null;

        $actor = $this->actorUserId !== null
            ? User::query()->find($this->actorUserId)
            : null;

        $user = $this->userId !== null
            ? User::query()->find($this->userId)
            : null;

        $processor->process($this->triggerSlug, $lead, $this->context, $actor, $user);
    }
}
