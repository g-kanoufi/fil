<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Services\Legacy\Concerns\ReadsLegacyDump;

final class LegacyNamedTableImporter
{
    use ReadsLegacyDump;

    /**
     * @param  callable(array<string, string|null>, bool): void  $onRow
     * @return array{matched: int, skipped: int}
     */
    public function import(
        string $dumpPath,
        string $tableName,
        callable $onRow,
        bool $execute,
    ): array {
        $stats = ['matched' => 0, 'skipped' => 0];
        $needle = 'INSERT INTO `'.$tableName.'`';
        $buffer = '';
        $columns = null;

        foreach ($this->readLines($dumpPath) as $line) {
            if ($columns === null) {
                if (! str_contains($line, $needle)) {
                    continue;
                }

                if (! preg_match('/INSERT INTO `[^`]+` \(([^)]+)\) VALUES/i', $line, $headerMatch)) {
                    continue;
                }

                $columns = array_map(
                    static fn (string $column): string => trim($column, " `\t\n\r"),
                    explode(',', $headerMatch[1]),
                );

                $valuesPos = stripos($line, 'VALUES');

                if ($valuesPos === false) {
                    continue;
                }

                $buffer = substr($line, $valuesPos + 6);
            } else {
                $buffer .= $line;
            }

            if (! str_contains($buffer, ';')) {
                continue;
            }

            $valuesSection = strstr($buffer, ';', true) ?: $buffer;

            foreach (LegacySqlFieldParser::splitTuples($valuesSection) as $tuple) {
                $fields = LegacySqlFieldParser::parseFields($tuple);

                if (count($fields) < count($columns)) {
                    $stats['skipped']++;

                    continue;
                }

                /** @var array<string, string|null> $row */
                $row = array_combine($columns, array_slice($fields, 0, count($columns)));

                if ($row === false) {
                    $stats['skipped']++;

                    continue;
                }

                $stats['matched']++;
                $onRow($row, $execute);
            }

            $columns = null;
            $buffer = '';
        }

        return $stats;
    }
}
