<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;

final class LegacyCommunicationImportService
{
    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
    ) {}

    /**
     * @return array{communications: int, skipped: int}
     */
    public function import(string $dumpPath, string $prefix, bool $execute): array
    {
        $table = $prefix.'z_communications';
        $stats = ['communications' => 0, 'skipped' => 0];

        $result = $this->importer->import(
            $dumpPath,
            $table,
            function (array $row, bool $execute) use (&$stats): void {
                $legacyId = (int) ($row['id'] ?? 0);
                $message = (string) ($row['message'] ?? '');

                if ($legacyId <= 0 || $message === '') {
                    $stats['skipped']++;

                    return;
                }

                $stats['communications']++;

                if (! $execute) {
                    return;
                }

                $recipientUserId = isset($row['recipient_id']) ? (int) $row['recipient_id'] : null;
                $leadId = $this->resolveLeadId($recipientUserId);
                $resolvedRecipientId = $this->resolveUserId($recipientUserId);

                Communication::query()->updateOrCreate(
                    ['external_message_id' => (string) ($row['message_id'] ?? 'legacy-'.$legacyId)],
                    [
                        'lead_id' => $leadId,
                        'recipient_user_id' => $resolvedRecipientId,
                        'recipient_name' => $row['recipient'] ?? null,
                        'sender_name' => $row['sender'] ?? null,
                        'type' => $row['type'] ?? 'sms',
                        'direction' => $row['direction'] ?? 'outbound',
                        'message' => $message,
                        'provider' => $row['provider'] ?? 'legacy',
                        'status' => filled($row['status'] ?? null) ? (string) $row['status'] : 'sent',
                        'errors' => filled($row['errors'] ?? null) ? (string) $row['errors'] : null,
                        'sent_at' => $this->parseTime($row['time'] ?? null),
                        'meta' => [
                            'legacy_comm_id' => $legacyId,
                            'legacy_sender_id' => $row['sender_id'] ?? null,
                            'legacy_meta' => $row['meta'] ?? null,
                        ],
                    ],
                );
            },
            $execute,
        );

        $stats['skipped'] += $result['skipped'];

        return $stats;
    }

    private function resolveLeadId(?int $userId): ?int
    {
        if ($userId === null || $userId <= 0) {
            return null;
        }

        $lead = Lead::query()
            ->where('prospect_user_id', $userId)
            ->orWhere('owner_user_id', $userId)
            ->orderByDesc('updated_at')
            ->first();

        return $lead?->id;
    }

    private function resolveUserId(?int $legacyOrLocalId): ?int
    {
        if ($legacyOrLocalId === null || $legacyOrLocalId <= 0) {
            return null;
        }

        $byLegacy = User::query()->where('legacy_user_id', $legacyOrLocalId)->value('id');

        if ($byLegacy !== null) {
            return (int) $byLegacy;
        }

        $byId = User::query()->whereKey($legacyOrLocalId)->value('id');

        return $byId !== null ? (int) $byId : null;
    }

    private function parseTime(?string $value): Carbon
    {
        if ($value === null || trim($value) === '') {
            return now();
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return now();
        }
    }
}
