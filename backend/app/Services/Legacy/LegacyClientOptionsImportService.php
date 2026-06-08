<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\ClientSetting;
use App\Support\Legacy\LegacyTableNames;

final class LegacyClientOptionsImportService
{
    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
    ) {}

    /**
     * @return array{applied: int, skipped: int}
     */
    public function import(string $dumpPath, string $sitePrefix, bool $execute): array
    {
        $tables = LegacyTableNames::fromSitePrefix($sitePrefix);
        $optionsTable = $tables->networkTable('options');

        /** @var array<string, string|null> $optionRows */
        $optionRows = [];
        $stats = ['applied' => 0, 'skipped' => 0];

        $this->importer->import(
            $dumpPath,
            $optionsTable,
            function (array $row) use (&$optionRows): void {
                $name = (string) ($row['option_name'] ?? '');

                if ($name === '') {
                    return;
                }

                $optionRows[$name] = $row['option_value'] ?? null;
            },
            false,
        );

        /** @var array<string, list<string>> $map */
        $map = config('fil-legacy-acf.client_settings', []);

        foreach ($map as $filKey => $legacyKeys) {
            $raw = $this->firstOptionValue($optionRows, $legacyKeys);

            if ($raw === null) {
                $stats['skipped']++;

                continue;
            }

            $value = $this->normalizeValue($filKey, $raw);

            if ($value === null) {
                $stats['skipped']++;

                continue;
            }

            $stats['applied']++;

            if ($execute) {
                ClientSetting::query()->updateOrCreate(
                    ['key' => $filKey],
                    ['value' => $value],
                );
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, string|null>  $optionRows
     * @param  list<string>  $legacyKeys
     */
    private function firstOptionValue(array $optionRows, array $legacyKeys): ?string
    {
        foreach ($legacyKeys as $legacyKey) {
            $candidates = str_starts_with($legacyKey, 'options_')
                ? [$legacyKey]
                : [$legacyKey, 'options_'.$legacyKey];

            foreach ($candidates as $optionName) {
                if (! array_key_exists($optionName, $optionRows)) {
                    continue;
                }

                $value = $optionRows[$optionName];

                if ($value === null || $value === '') {
                    continue;
                }

                return (string) $value;
            }
        }

        return null;
    }

    private function normalizeValue(string $filKey, string $raw): mixed
    {
        if (in_array($filKey, ['clientBranding', 'enable_zai'], true)) {
            return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
        }

        if (in_array($filKey, ['logoUrl', 'faviconUrl'], true)) {
            if (ctype_digit(trim($raw))) {
                return null;
            }

            $trimmed = trim($raw);

            return $trimmed !== '' ? $trimmed : null;
        }

        $trimmed = trim($raw);

        return $trimmed !== '' ? $trimmed : null;
    }
}
