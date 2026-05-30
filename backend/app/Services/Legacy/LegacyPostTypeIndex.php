<?php

declare(strict_types=1);

namespace App\Services\Legacy;

final class LegacyPostTypeIndex
{
    /** @var array<int, string> */
    private array $typesByLegacyPostId = [];

    /**
     * @return array<int, string>
     */
    public function build(string $dumpPath, string $prefix): array
    {
        $this->typesByLegacyPostId = [];
        $tableNeedle = 'INSERT INTO `'.$prefix.'posts`';

        foreach (LegacySqlInsertReader::statements($dumpPath, $tableNeedle) as $statement) {
            $valuesPos = stripos($statement, 'VALUES');

            if ($valuesPos === false) {
                continue;
            }

            $valuesSection = substr($statement, $valuesPos + 6);

            foreach (LegacySqlFieldParser::splitTuples($valuesSection) as $tuple) {
                $fields = LegacySqlFieldParser::parseFields($tuple);

                if (count($fields) < 21) {
                    continue;
                }

                $legacyPostId = (int) ($fields[0] ?? 0);
                $postType = (string) ($fields[20] ?? '');

                if ($legacyPostId > 0 && $postType !== '') {
                    $this->typesByLegacyPostId[$legacyPostId] = $postType;
                }
            }
        }

        return $this->typesByLegacyPostId;
    }

    public function typeFor(int $legacyPostId): ?string
    {
        return $this->typesByLegacyPostId[$legacyPostId] ?? null;
    }
}
