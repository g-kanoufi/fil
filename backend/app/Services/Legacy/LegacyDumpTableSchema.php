<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Services\Legacy\Concerns\ReadsLegacyDump;

/**
 * Resolves column order from mysqldump CREATE TABLE blocks (INSERT without column list).
 */
final class LegacyDumpTableSchema
{
    use ReadsLegacyDump;

    /** @var array<string, list<string>> */
    private static array $cache = [];

    /**
     * @return list<string>|null
     */
    public function columnNames(string $dumpPath, string $tableName): ?array
    {
        $cacheKey = $dumpPath.'|'.$tableName;

        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $needle = 'CREATE TABLE `'.$tableName.'`';
        $inCreate = false;
        $columns = [];

        foreach ($this->readLines($dumpPath) as $line) {
            if (! $inCreate) {
                if (! str_contains($line, $needle)) {
                    continue;
                }

                $inCreate = true;

                continue;
            }

            if (preg_match('/^\) ENGINE=/i', trim($line))) {
                break;
            }

            if (preg_match('/^\s*`([^`]+)`\s+/i', $line, $match)) {
                $columns[] = $match[1];
            }
        }

        self::$cache[$cacheKey] = $columns === [] ? null : $columns;

        return self::$cache[$cacheKey];
    }
}
