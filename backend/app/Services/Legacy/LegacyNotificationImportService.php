<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\NotificationRule;
use App\Services\Notifications\NotificationConditionNormalizer;
use App\Services\Notifications\NotificationRecipientTokenNormalizer;
use App\Services\Notifications\NotificationScheduleNormalizer;

final class LegacyNotificationImportService
{
    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
        private readonly NotificationConditionNormalizer $conditionNormalizer,
        private readonly NotificationScheduleNormalizer $scheduleNormalizer,
        private readonly NotificationRecipientTokenNormalizer $recipientNormalizer,
    ) {}

    /**
     * @return array{rules: int, skipped: int}
     */
    public function import(string $dumpPath, string $prefix, bool $execute): array
    {
        $carriersByHash = $this->loadSluggedRows($dumpPath, $prefix.'notification_carriers', true);
        $extrasByHash = $this->loadSluggedRows($dumpPath, $prefix.'notification_extras', false);

        $table = $prefix.'notifications';
        $stats = ['rules' => 0, 'skipped' => 0];

        $result = $this->importer->import(
            $dumpPath,
            $table,
            function (array $row, bool $execute) use (&$stats, $carriersByHash, $extrasByHash): void {
                $title = trim((string) ($row['title'] ?? $row['name'] ?? ''));

                if ($title === '') {
                    $stats['skipped']++;

                    return;
                }

                $stats['rules']++;

                if (! $execute) {
                    return;
                }

                $hash = filled($row['hash'] ?? null) ? (string) $row['hash'] : null;
                $config = $this->decodeJson($row['config'] ?? $row['settings'] ?? null) ?? [];
                $extras = $this->decodeJson($row['extras'] ?? null) ?? [];

                if ($hash !== null) {
                    if ($carriersByHash[$hash] ?? [] !== []) {
                        $config['carriers'] = array_merge($config['carriers'] ?? [], $carriersByHash[$hash]);
                    }

                    if ($extrasByHash[$hash] ?? [] !== []) {
                        $extras = array_merge($extras, $extrasByHash[$hash]);
                    }
                }

                $normalized = $this->normalizeRulePayload($config, $extras);
                $triggerSlug = (string) ($row['trigger'] ?? $row['trigger_slug'] ?? $config['trigger'] ?? 'legacy');

                NotificationRule::query()->updateOrCreate(
                    ['hash' => $hash ?? 'legacy-'.($row['id'] ?? uniqid())],
                    [
                        'title' => $title,
                        'trigger_slug' => $triggerSlug,
                        'enabled' => (bool) ((int) ($row['enabled'] ?? $row['active'] ?? $config['enabled'] ?? 0)),
                        'channel' => 'email',
                        'subject' => $normalized['subject'],
                        'body_html' => $normalized['body_html'],
                        'recipients' => $normalized['recipients'],
                        'conditionals' => $normalized['conditionals'],
                        'schedule' => $normalized['schedule'],
                        'profile_roles' => $normalized['profile_roles'],
                        'config' => $config,
                        'extras' => array_merge($extras, [
                            'legacy_id' => $row['id'] ?? null,
                        ]),
                    ],
                );
            },
            $execute,
        );

        $stats['skipped'] += $result['skipped'];

        return $stats;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadSluggedRows(string $dumpPath, string $table, bool $includeEnabled): array
    {
        $byHash = [];

        $this->importer->import(
            $dumpPath,
            $table,
            function (array $row, bool $execute) use (&$byHash, $includeEnabled): void {
                unset($execute);

                $hash = (string) ($row['notification_hash'] ?? '');
                $slug = (string) ($row['slug'] ?? '');

                if ($hash === '' || $slug === '') {
                    return;
                }

                $payload = $this->decodeJson($row['data'] ?? null) ?? [];
                $payload = is_array($payload) ? $payload : ['raw' => $payload];

                if ($includeEnabled) {
                    $payload['enabled'] = (bool) ((int) ($row['enabled'] ?? 0));
                }

                $byHash[$hash][$slug] = $payload;
            },
            false,
        );

        return $byHash;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $extras
     * @return array{subject: ?string, body_html: ?string, recipients: ?array, conditionals: ?array, schedule: ?array, profile_roles: ?array}
     */
    private function normalizeRulePayload(array $config, array $extras): array
    {
        $email = $config['carriers']['email'] ?? $config['email'] ?? [];

        if (! is_array($email)) {
            $email = [];
        }

        $recipients = null;

        foreach (['parsed_recipients', 'recipients', 'to'] as $key) {
            $candidate = $email[$key] ?? null;

            if (! is_array($candidate)) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    $recipients = $this->recipientNormalizer->normalizeList([$candidate]);
                }

                continue;
            }

            $normalized = $this->recipientNormalizer->normalizeList($candidate);

            if ($normalized !== []) {
                $recipients = $normalized;

                break;
            }
        }

        $rawConditionals = $extras['zrz_conditionals'] ?? $config['extras']['zrz_conditionals'] ?? null;
        $rawSchedule = $extras['schedule'] ?? $config['extras']['schedule'] ?? $config['schedule'] ?? null;

        return [
            'subject' => is_string($email['subject'] ?? null) ? $email['subject'] : null,
            'body_html' => is_string($email['body'] ?? null) ? $email['body'] : null,
            'recipients' => $recipients,
            'conditionals' => is_array($rawConditionals)
                ? $this->conditionNormalizer->normalize($rawConditionals)
                : null,
            'schedule' => is_array($rawSchedule)
                ? $this->scheduleNormalizer->normalize($rawSchedule)
                : null,
            'profile_roles' => $extras['zrz_notification_profile_roles']
                ?? $config['extras']['zrz_notification_profile_roles']
                ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode(stripcslashes($value), true);

        return is_array($decoded) ? $decoded : ['raw' => $value];
    }
}
