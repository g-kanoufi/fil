<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Jobs\Notifications\ProcessNotificationTriggerJob;
use App\Models\Lead;
use App\Models\NotificationLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class NotificationDispatcher
{
    /**
     * Queue notification processing for a trigger slug.
     *
     * @param  array<string, mixed>  $context
     */
    public function dispatch(
        string $triggerSlug,
        ?Lead $lead = null,
        array $context = [],
        ?User $actor = null,
        ?User $user = null,
    ): void {
        if ($lead === null && $user === null) {
            Log::debug('Notification trigger skipped — no lead or user context', ['trigger' => $triggerSlug]);

            return;
        }

        ProcessNotificationTriggerJob::dispatch(
            $triggerSlug,
            $lead?->id,
            $context,
            $actor?->id,
            $user?->id,
            null,
        )->onQueue(config('fil-notifications.queues.notifications', 'notifications'));

        NotificationLog::query()->create([
            'type' => 'trigger',
            'component' => $triggerSlug,
            'message' => 'Notification trigger queued',
            'logged_at' => now(),
            'meta' => [
                'lead_id' => $lead?->id,
                'user_id' => $user?->id,
                'actor_user_id' => $actor?->id,
                'context' => $context,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function dispatchStore(
        string $triggerSlug,
        Store $store,
        array $context = [],
        ?User $actor = null,
    ): void {
        ProcessNotificationTriggerJob::dispatch(
            $triggerSlug,
            null,
            $context,
            $actor?->id,
            null,
            $store->id,
        )->onQueue(config('fil-notifications.queues.notifications', 'notifications'));

        NotificationLog::query()->create([
            'type' => 'trigger',
            'component' => $triggerSlug,
            'message' => 'Store notification trigger queued',
            'logged_at' => now(),
            'meta' => [
                'store_id' => $store->id,
                'actor_user_id' => $actor?->id,
                'context' => $context,
            ],
        ]);
    }
}
