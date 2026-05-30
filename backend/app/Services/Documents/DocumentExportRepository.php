<?php

declare(strict_types=1);

namespace App\Services\Documents;

use Illuminate\Support\Facades\Cache;

final class DocumentExportRepository
{
    private const TTL_SECONDS = 86400;

    /**
     * @param  array<string, mixed>  $state
     */
    public function put(string $exportId, array $state): void
    {
        Cache::put($this->key($exportId), $state, self::TTL_SECONDS);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $exportId): ?array
    {
        $state = Cache::get($this->key($exportId));

        return is_array($state) ? $state : null;
    }

    private function key(string $exportId): string
    {
        return 'document_export:'.$exportId;
    }
}
