<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\AiMessage;
use App\Models\AiThread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class LegacyAiThreadImportService
{
    /** @var array<string, int> */
    private array $threadMap = [];

    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
    ) {}

    /**
     * @return array{threads: int, messages: int, skipped: int}
     */
    public function import(string $dumpPath, string $prefix, bool $execute): array
    {
        $stats = ['threads' => 0, 'messages' => 0, 'skipped' => 0];
        $seenThreads = [];

        $result = $this->importer->import(
            $dumpPath,
            $prefix.'zai_chats',
            function (array $row, bool $execute) use (&$stats, &$seenThreads): void {
                $legacyId = (int) ($row['id'] ?? 0);
                $externalThreadId = trim((string) ($row['thread_id'] ?? ''));
                $legacyUserId = (int) ($row['user_id'] ?? 0);
                $message = trim((string) ($row['message'] ?? ''));

                if ($legacyId <= 0 || $externalThreadId === '' || $message === '') {
                    $stats['skipped']++;

                    return;
                }

                $userId = User::query()->where('legacy_user_id', $legacyUserId)->value('id')
                    ?? User::query()->whereKey($legacyUserId)->value('id');

                if ($userId === null) {
                    $stats['skipped']++;

                    return;
                }

                if (! isset($seenThreads[$externalThreadId])) {
                    $seenThreads[$externalThreadId] = true;
                    $stats['threads']++;
                }

                $stats['messages']++;

                if (! $execute) {
                    return;
                }

                $thread = AiThread::query()->firstOrCreate(
                    ['external_thread_id' => $externalThreadId],
                    [
                        'user_id' => (int) $userId,
                        'title' => Str::limit($message, 80),
                        'status' => 'imported',
                        'legacy_chat_id' => $legacyId,
                        'meta' => ['imported_from' => 'zai_chats'],
                    ],
                );

                AiMessage::query()->create([
                    'ai_thread_id' => $thread->id,
                    'role' => 'user',
                    'content' => $message,
                    'created_at' => $this->parseTime($row['created'] ?? null),
                    'meta' => [
                        'legacy_chat_id' => $legacyId,
                        'sources' => $row['sources'] ?? null,
                        'confidence' => $row['confidence'] ?? null,
                    ],
                ]);

                $thread->touch();
            },
            $execute,
        );

        $stats['skipped'] += $result['skipped'];

        return $stats;
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
