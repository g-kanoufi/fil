<?php

declare(strict_types=1);

namespace App\Services\Legacy;

final class LegacyPostTypeIndex
{
    /** @var array<int, string> */
    private array $typesByLegacyPostId = [];

    /** @var array<int, string> */
    private array $statusByLegacyPostId = [];

    /**
     * @return array<int, string>
     */
    public function build(string $dumpPath, string $prefix): array
    {
        $this->typesByLegacyPostId = [];
        $this->statusByLegacyPostId = [];
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
                $postStatus = (string) ($fields[7] ?? '');
                $postType = (string) ($fields[20] ?? '');

                if ($legacyPostId > 0 && $postType !== '') {
                    $this->typesByLegacyPostId[$legacyPostId] = $postType;
                    $this->statusByLegacyPostId[$legacyPostId] = $postStatus;
                }
            }
        }

        return $this->typesByLegacyPostId;
    }

    /**
     * @return array<int, string>
     */
    public function typesByLegacyPostId(): array
    {
        return $this->typesByLegacyPostId;
    }

    public function typeFor(int $legacyPostId): ?string
    {
        return $this->typesByLegacyPostId[$legacyPostId] ?? null;
    }

    public function isEligible(int $legacyPostId): bool
    {
        $postType = $this->typeFor($legacyPostId);

        if ($postType === null) {
            return true;
        }

        $status = $this->statusByLegacyPostId[$legacyPostId] ?? '';

        if (in_array($status, ['trash', 'auto-draft', 'inherit'], true)) {
            return false;
        }

        /** @var list<string> $importable */
        $importable = config('fil-legacy-acf.importable_post_types', [
            'application', 'store', 'franchise_location', 'area', 'organization',
        ]);

        return in_array($postType, $importable, true);
    }
}
