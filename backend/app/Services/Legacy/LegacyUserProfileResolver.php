<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Services\Legacy\Concerns\ReadsLegacyDump;
use App\Support\Legacy\LegacyTableNames;

/**
 * Pre-scans wp_usermeta for profile fields and site capabilities.
 */
final class LegacyUserProfileResolver
{
    use ReadsLegacyDump;

    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
    ) {}

    /**
     * @return array<int, array{wp_roles: list<string>, first_name: ?string, last_name: ?string, phone: ?string}>
     */
    public function resolve(string $dumpPath, string $sitePrefix): array
    {
        $tables = LegacyTableNames::fromSitePrefix($sitePrefix);
        $table = $tables->networkTable('usermeta');
        $capabilitiesKey = $sitePrefix.'capabilities';
        $profiles = [];

        foreach ($this->readLines($dumpPath) as $line) {
            if (! str_contains($line, $capabilitiesKey)) {
                continue;
            }

            $pattern = '/\(\d+,\s*(\d+),\s*\''
                .preg_quote($capabilitiesKey, '/')
                .'\',\s*\'(a:\d+:\{[^\']*\})\'\)/';

            if (preg_match_all($pattern, $line, $capMatches, PREG_SET_ORDER)) {
                foreach ($capMatches as $match) {
                    $userId = (int) $match[1];
                    $profiles[$userId] = array_merge($this->emptyProfile(), $profiles[$userId] ?? []);
                    $profiles[$userId]['wp_roles'] = LegacyWpCapabilitiesParser::parseRoles($match[2]);
                }
            }
        }

        $simpleKeys = ['first_name', 'last_name', 'phone', 'billing_phone', 'mobile_phone'];

        $this->importer->import(
            $dumpPath,
            $table,
            function (array $row, bool $execute) use (&$profiles, $simpleKeys): void {
                unset($execute);

                $userId = (int) ($row['user_id'] ?? 0);
                $metaKey = (string) ($row['meta_key'] ?? '');
                $metaValue = $row['meta_value'] ?? null;

                if ($userId <= 0 || ! in_array($metaKey, $simpleKeys, true)) {
                    return;
                }

                if (! isset($profiles[$userId])) {
                    $profiles[$userId] = $this->emptyProfile();
                }

                if ($metaKey === 'first_name' && filled($metaValue)) {
                    $profiles[$userId]['first_name'] = $metaValue;
                } elseif ($metaKey === 'last_name' && filled($metaValue)) {
                    $profiles[$userId]['last_name'] = $metaValue;
                } elseif (in_array($metaKey, ['phone', 'billing_phone', 'mobile_phone'], true) && filled($metaValue)) {
                    $profiles[$userId]['phone'] ??= $metaValue;
                }
            },
            false,
        );

        return $profiles;
    }

    /**
     * @return array{wp_roles: list<string>, first_name: ?string, last_name: ?string, phone: ?string}
     */
    private function emptyProfile(): array
    {
        return [
            'wp_roles' => [],
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
        ];
    }
}
