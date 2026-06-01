<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$prefix = 'vnzokz0zw_9_';
$dump = __DIR__.'/../../data/local.sql.gz';

/** @var array<int, array{name: ?string, slug: ?string}> $terms */
$terms = [];

foreach (App\Services\Legacy\LegacySqlInsertReader::statements($dump, 'INSERT INTO `'.$prefix.'terms`') as $statement) {
    $valuesPos = stripos($statement, 'VALUES');

    if ($valuesPos === false) {
        continue;
    }

    foreach (App\Services\Legacy\LegacySqlFieldParser::splitTuples(substr($statement, $valuesPos + 6)) as $tuple) {
        $fields = App\Services\Legacy\LegacySqlFieldParser::parseFields($tuple);

        if (count($fields) < 3) {
            continue;
        }

        $terms[(int) $fields[0]] = [
            'name' => $fields[1],
            'slug' => $fields[2],
        ];
    }
}

/** @var array<int, array{parent: int}> $tax */
$tax = [];

foreach (App\Services\Legacy\LegacySqlInsertReader::statements($dump, 'INSERT INTO `'.$prefix.'term_taxonomy`') as $statement) {
    $valuesPos = stripos($statement, 'VALUES');

    if ($valuesPos === false) {
        continue;
    }

    foreach (App\Services\Legacy\LegacySqlFieldParser::splitTuples(substr($statement, $valuesPos + 6)) as $tuple) {
        $fields = App\Services\Legacy\LegacySqlFieldParser::parseFields($tuple);

        if (count($fields) < 6 || $fields[2] !== 'grabba_tax_area') {
            continue;
        }

        $tax[(int) $fields[1]] = [
            'parent' => (int) $fields[4],
        ];
    }
}

echo 'terms='.count($terms).' grabba_tax_area='.count($tax).PHP_EOL;

foreach ($tax as $termId => $meta) {
    $term = $terms[$termId] ?? null;

    if ($term === null) {
        continue;
    }

    echo implode('|', [$termId, $meta['parent'], $term['name'], $term['slug']]).PHP_EOL;
}
