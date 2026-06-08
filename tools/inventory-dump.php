#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Inventory a legacy multisite SQL dump for FIL import planning.
 *
 * Usage: php tools/inventory-dump.php [path/to/dump.sql.gz] [--prefix=vnzokz0zw_9_]
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$path = $argv[1] ?? __DIR__ . '/../data/local.sql.gz';
$prefix = 'vnzokz0zw_9_';

foreach (array_slice($argv, 2) as $arg) {
    if (str_starts_with($arg, '--prefix=')) {
        $prefix = substr($arg, 9);
    }
}

if (! is_readable($path)) {
    fwrite(STDERR, "Dump not readable: {$path}\n");
    exit(1);
}

$open = str_ends_with($path, '.gz')
    ? gzopen($path, 'rb')
    : fopen($path, 'rb');

if ($open === false) {
    fwrite(STDERR, "Cannot open dump: {$path}\n");
    exit(1);
}

$postTypes = [];
$customTables = [];
$tableRowCounts = [];
$lineNo = 0;
$inPostsInsert = false;

$readLine = function () use (&$open, &$lineNo, $path) {
    $lineNo++;
    if (str_ends_with($path, '.gz')) {
        return gzgets($open);
    }

    return fgets($open);
};

while (($line = $readLine()) !== false) {
    if (preg_match('/CREATE TABLE `' . preg_quote($prefix, '/') . '([^`]+)`/', $line, $m)) {
        $customTables[] = $m[1];
    }

    if (preg_match('/INSERT INTO `([^`]+)`/', $line, $networkMatch)) {
        $fullTable = $networkMatch[1];

        if (preg_match('/^(.+?)(?:_\d+_)(users|usermeta|options)$/', $fullTable, $networkParts)) {
            $tableRowCounts['network:'.$networkParts[2]] = ($tableRowCounts['network:'.$networkParts[2]] ?? 0) + substr_count($line, '),(') + 1;
        }
    }

    if (preg_match('/INSERT INTO `' . preg_quote($prefix, '/') . '([^`]+)`/', $line, $m)) {
        $tableRowCounts[$m[1]] = ($tableRowCounts[$m[1]] ?? 0) + substr_count($line, '),(') + 1;
    }

    if (preg_match('/INSERT INTO `vnzokz0zw_users`/', $line)) {
        $tableRowCounts['users'] = ($tableRowCounts['users'] ?? 0) + substr_count($line, '),(') + 1;
    }

    if (preg_match('/INSERT INTO `' . preg_quote($prefix, '/') . 'posts`/', $line)) {
        $inPostsInsert = true;
    }

    if ($inPostsInsert) {
        if (preg_match_all(
            "/, '(application|store|grabbafdd|areafdd|organization|closing|area)'(?:, '[^']*',|, '',) \\d+\\)/",
            $line,
            $matches,
        )) {
            foreach ($matches[1] as $type) {
                $postTypes[$type] = ($postTypes[$type] ?? 0) + 1;
            }
        }

        if (str_contains($line, ';')) {
            $inPostsInsert = false;
        }
    }
}

if (str_ends_with($path, '.gz')) {
    gzclose($open);
} else {
    fclose($open);
}

ksort($postTypes);
sort($customTables);

echo "FIL legacy dump inventory\n";
echo "=========================\n";
echo "File: {$path}\n";
echo "Site prefix: {$prefix}\n\n";

echo "Post type occurrences (approx, from INSERT lines):\n";
foreach ($postTypes as $type => $count) {
    echo "  - {$type}: {$count}\n";
}

echo "\nCustom tables ({$prefix}*):\n";
foreach ($customTables as $table) {
    echo "  - {$table}\n";
}

if ($tableRowCounts !== []) {
    ksort($tableRowCounts);
    echo "\nTable row counts (approx, from INSERT lines):\n";
    foreach ($tableRowCounts as $table => $count) {
        echo "  - {$table}: {$count}\n";
    }
}

echo "\nDone.\n";
