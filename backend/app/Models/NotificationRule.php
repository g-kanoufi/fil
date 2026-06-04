<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Notifications\NotificationConditionNormalizer;
use App\Services\Notifications\NotificationRecipientTokenNormalizer;
use App\Services\Notifications\NotificationScheduleNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NotificationRule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'hash',
        'title',
        'trigger_slug',
        'channel',
        'subject',
        'body_html',
        'recipients',
        'conditionals',
        'schedule',
        'profile_roles',
        'enabled',
        'config',
        'extras',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'recipients' => 'array',
            'conditionals' => 'array',
            'schedule' => 'array',
            'profile_roles' => 'array',
            'config' => 'array',
            'extras' => 'array',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }

    public function normalizedTriggerSlug(): string
    {
        /** @var array<string, string> $map */
        $map = config('fil-notifications.legacy_trigger_map', []);

        return $map[$this->trigger_slug] ?? $this->trigger_slug;
    }

    /**
     * @return list<string>
     */
    public function recipientTokens(): array
    {
        $normalizer = app(NotificationRecipientTokenNormalizer::class);

        if (is_array($this->recipients) && $this->recipients !== []) {
            $normalized = $normalizer->normalizeList($this->recipients);

            if ($normalized !== []) {
                return $normalized;
            }
        }

        $email = $this->config['carriers']['email'] ?? $this->config['email'] ?? null;

        if (! is_array($email)) {
            return ['related:prospect'];
        }

        foreach (['parsed_recipients', 'recipients', 'to'] as $key) {
            $raw = $email[$key] ?? null;

            if (! is_array($raw)) {
                if (is_string($raw) && trim($raw) !== '') {
                    return $normalizer->normalizeList([$raw]);
                }

                continue;
            }

            $normalized = $normalizer->normalizeList($raw);

            if ($normalized !== []) {
                return $normalized;
            }
        }

        return ['related:prospect'];
    }

    public function emailSubjectTemplate(): string
    {
        return $this->subject
            ?? $this->config['carriers']['email']['subject']
            ?? $this->config['email']['subject']
            ?? $this->title;
    }

    public function emailBodyTemplate(): string
    {
        return $this->body_html
            ?? $this->config['carriers']['email']['body']
            ?? $this->config['email']['body']
            ?? '<p>Notification: '.$this->title.'</p>';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function conditionalRules(): ?array
    {
        $raw = $this->conditionals
            ?? $this->extras['zrz_conditionals']
            ?? $this->config['extras']['zrz_conditionals']
            ?? null;

        if (! is_array($raw)) {
            return null;
        }

        return app(NotificationConditionNormalizer::class)->normalize($raw);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function effectiveSchedule(): ?array
    {
        if (is_array($this->schedule) && filled($this->schedule['field'] ?? null)) {
            return app(NotificationScheduleNormalizer::class)
                ->normalize($this->schedule);
        }

        $legacy = $this->extras['schedule']
            ?? $this->config['extras']['schedule']
            ?? $this->config['schedule']
            ?? null;

        if (! is_array($legacy)) {
            return null;
        }

        return app(NotificationScheduleNormalizer::class)->normalize($legacy);
    }

    public function isScheduledTrigger(): bool
    {
        if ($this->effectiveSchedule() !== null) {
            return true;
        }

        return $this->trigger_slug === 'scheduled.leads'
            || str_starts_with($this->trigger_slug, 'scheduled/');
    }

    public function shouldSkipDuplicateSend(Lead $lead): bool
    {
        if (! $this->isScheduledTrigger()) {
            return false;
        }

        $schedule = $this->effectiveSchedule();
        $sendOnce = $schedule['send_once'] ?? true;

        if (! $sendOnce) {
            return false;
        }

        return $this->deliveries()
            ->where('lead_id', $lead->id)
            ->whereIn('status', ['queued', 'sent'])
            ->exists();
    }

    public function shouldSkipDuplicateSendForStore(Store $store): bool
    {
        if (! str_starts_with($this->normalizedTriggerSlug(), 'store.')) {
            return false;
        }

        return $this->deliveries()
            ->whereIn('status', ['queued', 'sent'])
            ->get()
            ->contains(fn (NotificationDelivery $delivery): bool => (int) ($delivery->meta['store_id'] ?? 0) === $store->id);
    }
}
