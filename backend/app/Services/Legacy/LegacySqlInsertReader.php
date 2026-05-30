<?php

declare(strict_types=1);

namespace App\Services\Legacy;

final class LegacySqlInsertReader
{
    /**
     * Yields complete INSERT statement bodies (from INSERT through trailing semicolon).
     *
     * @return \Generator<int, string>
     */
    public static function statements(string $dumpPath, string $tableNeedle): \Generator
    {
        $handle = str_ends_with($dumpPath, '.gz') ? gzopen($dumpPath, 'rb') : fopen($dumpPath, 'rb');

        if ($handle === false) {
            return;
        }

        $buffer = '';

        try {
            while (true) {
                $line = str_ends_with($dumpPath, '.gz') ? gzgets($handle) : fgets($handle);

                if ($line === false) {
                    break;
                }

                if ($buffer === '' && ! str_contains($line, $tableNeedle)) {
                    continue;
                }

                $buffer .= $line;

                if (str_contains($line, ';')) {
                    yield $buffer;
                    $buffer = '';
                }
            }
        } finally {
            if (str_ends_with($dumpPath, '.gz')) {
                gzclose($handle);
            } else {
                fclose($handle);
            }
        }
    }
}
