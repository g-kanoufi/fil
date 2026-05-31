<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\ActivityEvent;
use App\Models\Communication;
use App\Models\FddDelivery;
use App\Models\Lead;
use App\Models\LeadPhaseEvent;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use App\Services\Leads\LeadPipelineCatalog;
use Carbon\CarbonImmutable;

final class ActivityFeedService
{
    public function __construct(
        private readonly LeadPipelineCatalog $pipelineCatalog,
        private readonly ResourceScopeService $scope,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, next_cursor: string|null, has_more: bool}
     */
    public function globalFeed(
        User $user,
        int $limit = 25,
        ?string $cursor = null,
        int $days = 30,
        ?int $actorUserId = null,
        ?string $category = null,
    ): array {
        $limit = min(max($limit, 1), (int) config('fil-activity.max_page_size', 50));
        $since = now()->subDays(max($days, 1));

        $query = ActivityEvent::query()
            ->where('occurred_at', '>=', $since)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if ($actorUserId !== null) {
            $query->where('actor_user_id', $actorUserId);
        }

        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        $this->scope->applyActivityEventScope($query, $user);

        if ($cursor !== null) {
            [$cursorAt, $cursorId] = $this->decodeCursor($cursor);
            $query->where(function ($builder) use ($cursorAt, $cursorId): void {
                $builder->where('occurred_at', '<', $cursorAt)
                    ->orWhere(function ($nested) use ($cursorAt, $cursorId): void {
                        $nested->where('occurred_at', '=', $cursorAt)
                            ->where('id', '<', $cursorId);
                    });
            });
        }

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;

        if ($hasMore) {
            $rows = $rows->take($limit);
        }

        $items = $rows->map(fn (ActivityEvent $event): array => $this->formatEvent($event))->all();

        $last = $rows->last();

        return [
            'items' => $items,
            'next_cursor' => $hasMore && $last instanceof ActivityEvent
                ? $this->encodeCursor($last->occurred_at, (int) $last->id)
                : null,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Export audited activity events matching the same scope and filters as the
     * global feed, ordered newest-first and capped at the configured export limit.
     *
     * @return list<array<string, mixed>>
     */
    public function exportEvents(
        User $user,
        int $days = 30,
        ?int $actorUserId = null,
        ?string $category = null,
    ): array {
        $maxRows = (int) config('fil-activity.max_export_rows', 10000);
        $since = now()->subDays(max($days, 1));

        $query = ActivityEvent::query()
            ->where('occurred_at', '>=', $since)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if ($actorUserId !== null) {
            $query->where('actor_user_id', $actorUserId);
        }

        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        $this->scope->applyActivityEventScope($query, $user);

        return $query->limit($maxRows)
            ->get()
            ->map(fn (ActivityEvent $event): array => $this->formatEvent($event))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function subjectTimeline(string $subjectType, int $subjectId, int $limit = 25): array
    {
        $limit = min(max($limit, 1), (int) config('fil-activity.max_page_size', 50));

        $groups = [
            $this->nativeSubjectEvents($subjectType, $subjectId, $limit),
        ];

        if ($subjectType === 'lead') {
            $groups[] = $this->projectLeadPhaseEvents($subjectId, $limit);
            $groups[] = $this->projectCommunicationsForLead($subjectId, $limit);
            $groups[] = $this->projectFddDeliveries($subjectId, $limit);
        }

        if ($subjectType === 'contact' || $subjectType === 'user') {
            $groups[] = $this->projectCommunicationsForRecipient($subjectId, $limit);
        }

        return $this->mergeSortedItems($groups, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function nativeSubjectEvents(string $subjectType, int $subjectId, int $limit): array
    {
        return ActivityEvent::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (ActivityEvent $event): array => $this->formatEvent($event))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function projectLeadPhaseEvents(int $leadId, int $limit): array
    {
        $lead = Lead::query()->find($leadId);
        $subject = $lead !== null ? $this->subjectPayload('lead', $leadId, $lead->title) : null;

        return LeadPhaseEvent::query()
            ->with('actor')
            ->where('lead_id', $leadId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (LeadPhaseEvent $event) use ($subject): array {
                $fromLabel = $event->from_phase !== null
                    ? $this->pipelineCatalog->phaseLabel((int) $event->from_phase)
                    : 'Start';
                $toLabel = $this->pipelineCatalog->phaseLabel((int) $event->to_phase);
                $actorName = $event->actor?->name ?? 'System';

                return [
                    'id' => "phase_{$event->id}",
                    'occurred_at' => $event->created_at?->toIso8601String(),
                    'actor' => [
                        'id' => $event->actor_user_id,
                        'name' => $actorName,
                    ],
                    'category' => 'lead',
                    'action' => 'transitioned',
                    'summary' => "{$actorName} moved pipeline from {$fromLabel} to {$toLabel}",
                    'subject' => $subject,
                    'source' => 'projection',
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function projectCommunicationsForLead(int $leadId, int $limit): array
    {
        $lead = Lead::query()->find($leadId);
        $subject = $lead !== null ? $this->subjectPayload('lead', $leadId, $lead->title) : null;

        return Communication::query()
            ->where('lead_id', $leadId)
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get()
            ->map(fn (Communication $communication): array => $this->formatCommunicationProjection($communication, $subject))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function projectCommunicationsForRecipient(int $userId, int $limit): array
    {
        $user = User::query()->find($userId);
        $subject = $user !== null
            ? $this->subjectPayload('contact', $userId, $user->name)
            : null;

        return Communication::query()
            ->where('recipient_user_id', $userId)
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get()
            ->map(fn (Communication $communication): array => $this->formatCommunicationProjection(
                $communication,
                $communication->lead_id !== null
                    ? $this->subjectPayload('lead', (int) $communication->lead_id, $this->subjectLabel('lead', (int) $communication->lead_id))
                    : $subject,
            ))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function projectFddDeliveries(int $leadId, int $limit): array
    {
        $lead = Lead::query()->find($leadId);
        $subject = $lead !== null ? $this->subjectPayload('lead', $leadId, $lead->title) : null;

        return FddDelivery::query()
            ->with(['fdd', 'latestSignature'])
            ->where('lead_id', $leadId)
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get()
            ->map(function (FddDelivery $delivery) use ($subject): array {
                $fddTitle = $delivery->fdd?->title ?? 'FDD';
                $signed = $delivery->latestSignature?->status === 'signed'
                    || $delivery->status === 'signed';

                return [
                    'id' => "fdd_{$delivery->id}",
                    'occurred_at' => ($signed && $delivery->latestSignature?->signed_at !== null
                        ? $delivery->latestSignature->signed_at
                        : $delivery->sent_at)?->toIso8601String(),
                    'actor' => [
                        'id' => null,
                        'name' => 'System',
                    ],
                    'category' => 'fdd',
                    'action' => $signed ? 'signed' : 'sent',
                    'summary' => $signed
                        ? "FDD \"{$fddTitle}\" signed"
                        : "FDD \"{$fddTitle}\" sent",
                    'subject' => $subject,
                    'source' => 'projection',
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $subject
     * @return array<string, mixed>
     */
    private function formatCommunicationProjection(Communication $communication, ?array $subject): array
    {
        $recipient = $communication->recipient_name ?? 'recipient';
        $channel = strtoupper($communication->type);
        $direction = $communication->direction === 'inbound' ? 'received' : 'sent';
        $sender = $communication->sender_name ?? 'Staff';

        return [
            'id' => "comm_{$communication->id}",
            'occurred_at' => $communication->sent_at?->toIso8601String(),
            'actor' => [
                'id' => $communication->sender_user_id,
                'name' => $sender,
            ],
            'category' => 'comm',
            'action' => $direction === 'received' ? 'delivered' : 'sent',
            'summary' => "{$channel} {$direction} to {$recipient}",
            'subject' => $subject,
            'source' => 'projection',
        ];
    }

    /**
     * @param  list<list<array<string, mixed>>>  $groups
     * @return list<array<string, mixed>>
     */
    private function mergeSortedItems(array $groups, int $limit): array
    {
        $items = [];

        foreach ($groups as $group) {
            foreach ($group as $item) {
                $items[] = $item;
            }
        }

        usort($items, function (array $left, array $right): int {
            $leftAt = (string) ($left['occurred_at'] ?? '');
            $rightAt = (string) ($right['occurred_at'] ?? '');

            return strcmp($rightAt, $leftAt);
        });

        return array_slice($items, 0, $limit);
    }

    /**
     * @return array{type: string, id: int, label: string, path: string|null}
     */
    private function subjectPayload(string $type, int $id, ?string $label = null): array
    {
        return [
            'type' => $type,
            'id' => $id,
            'label' => $label ?? $this->subjectLabel($type, $id),
            'path' => $this->subjectPath($type, $id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEvent(ActivityEvent $event): array
    {
        $subject = null;

        if ($event->subject_type !== null && $event->subject_id !== null) {
            $subject = $this->subjectPayload($event->subject_type, $event->subject_id);
        }

        return [
            'id' => "evt_{$event->id}",
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'actor' => [
                'id' => $event->actor_user_id,
                'name' => $event->actor_name,
            ],
            'category' => $event->category,
            'action' => $event->action,
            'summary' => $event->summary,
            'subject' => $subject,
            'source' => $event->source,
        ];
    }

    private function subjectLabel(string $type, int $id): string
    {
        return match ($type) {
            'lead' => Lead::query()->find($id)?->title ?? "Lead #{$id}",
            'store' => Store::query()->find($id)?->name ?? "Store #{$id}",
            'contact', 'user' => User::query()->find($id)?->name ?? "Contact #{$id}",
            default => ucfirst($type)." #{$id}",
        };
    }

    private function subjectPath(string $type, int $id): ?string
    {
        return match ($type) {
            'lead' => "/reports/leads/{$id}",
            'store' => "/reports/stores/{$id}",
            'contact', 'user' => "/reports/contacts/{$id}",
            default => null,
        };
    }

    private function encodeCursor(\DateTimeInterface $occurredAt, int $id): string
    {
        $payload = json_encode([
            'occurred_at' => CarbonImmutable::instance($occurredAt)->toIso8601String(),
            'id' => $id,
        ], JSON_THROW_ON_ERROR);

        return base64_encode($payload);
    }

    /**
     * @return array{0: CarbonImmutable, 1: int}
     */
    private function decodeCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid activity cursor.');
        }

        /** @var array{occurred_at?: string, id?: int} $payload */
        $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);

        if (! isset($payload['occurred_at'], $payload['id'])) {
            throw new \InvalidArgumentException('Invalid activity cursor payload.');
        }

        return [CarbonImmutable::parse($payload['occurred_at']), (int) $payload['id']];
    }
}
