<?php

declare(strict_types=1);

namespace App\Services\Legacy\Concerns;

trait ReadsLegacyDump
{
    /**
     * @return \Generator<int, string>
     */
    protected function readLines(string $dumpPath): \Generator
    {
        $handle = str_ends_with($dumpPath, '.gz') ? gzopen($dumpPath, 'rb') : fopen($dumpPath, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            while (true) {
                $line = str_ends_with($dumpPath, '.gz') ? gzgets($handle) : fgets($handle);

                if ($line === false) {
                    break;
                }

                yield $line;
            }
        } finally {
            if (str_ends_with($dumpPath, '.gz')) {
                gzclose($handle);
            } else {
                fclose($handle);
            }
        }
    }

    protected function networkPrefix(string $sitePrefix): string
    {
        if (preg_match('/^(.+?)(?:_\d+_)$/', $sitePrefix, $matches)) {
            return $matches[1].'_';
        }

        return $sitePrefix;
    }
}
