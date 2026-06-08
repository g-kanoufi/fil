<?php

declare(strict_types=1);

namespace App\Services\Documents;

final class DocumentLinkRoleShortener
{
    public const MAX_LENGTH = 32;

    /** @var array<string, string> */
    private const TOKEN_ALIASES = [
        'construction' => 'constr',
        'documents' => 'docs',
        'completed' => 'done',
        'group' => 'grp',
        'website' => 'web',
        'amendment' => 'amend',
        'extension' => 'ext',
        'options' => 'opts',
        'before' => 'bfr',
        'after' => 'aft',
        'distributor' => 'dist',
        'equipment' => 'equip',
        'finished' => 'fin',
        'supporting' => 'support',
        'assembled' => 'asm',
        'reception' => 'recv',
        'compounding' => 'compound',
        'injection' => 'inject',
        'cryotherapy' => 'cryo',
        'agreements' => 'agree',
        'agreement' => 'agree',
        'property' => 'prop',
        'texture' => 'texture',
        'drywall' => 'drywall',
        'framing' => 'framing',
        'laminar' => 'laminar',
        'member' => 'member',
        'non' => 'non',
        'the' => '',
        'of' => '',
        'and' => '',
        'for' => '',
    ];

    /** @var array<string, string> */
    private array $usedShortRoles = [];

    public function forStorage(string $role): string
    {
        if (strlen($role) <= self::MAX_LENGTH) {
            return $role;
        }

        $override = config('fil-documents.role_short_names.'.$role);

        if (is_string($override) && $override !== '') {
            return $this->ensureFits($override, $role);
        }

        return $this->ensureFits($this->abbreviate($role), $role);
    }

    private function abbreviate(string $role): string
    {
        $segments = $this->aliasSegments(explode('_', $role));
        $segments = $this->dropRedundantSegments($segments);

        $short = implode('_', $segments);

        if (strlen($short) <= self::MAX_LENGTH) {
            return $short;
        }

        $compressed = $this->compressSegments($segments);

        if (strlen($compressed) <= self::MAX_LENGTH) {
            return $compressed;
        }

        return substr($compressed, 0, 24).'_'.substr(sha1($role), 0, 7);
    }

    /**
     * @param  list<string>  $segments
     * @return list<string>
     */
    private function aliasSegments(array $segments): array
    {
        $aliased = [];

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            $mapped = self::TOKEN_ALIASES[$segment] ?? $segment;

            if ($mapped !== '') {
                $aliased[] = $mapped;
            }
        }

        return $aliased;
    }

    /**
     * Drop repeated context segments (e.g. distributor_documents_distributor → dist_docs).
     *
     * @param  list<string>  $segments
     * @return list<string>
     */
    private function dropRedundantSegments(array $segments): array
    {
        $result = [];

        foreach ($segments as $index => $segment) {
            if ($index >= 2 && $segment === $segments[$index - 2]) {
                continue;
            }

            $result[] = $segment;
        }

        return $result;
    }

    /**
     * @param  list<string>  $segments
     */
    private function compressSegments(array $segments): string
    {
        if ($segments === []) {
            return '';
        }

        if (count($segments) === 1) {
            return substr($segments[0], 0, self::MAX_LENGTH);
        }

        $head = array_shift($segments);
        $tail = array_pop($segments);
        $middle = array_map(
            static fn (string $segment): string => strlen($segment) > 4 ? substr($segment, 0, 4) : $segment,
            $segments,
        );

        return implode('_', array_filter([$head, ...$middle, $tail], static fn (string $s): bool => $s !== ''));
    }

    private function ensureFits(string $candidate, string $sourceRole): string
    {
        if (strlen($candidate) > self::MAX_LENGTH) {
            $candidate = substr($candidate, 0, 24).'_'.substr(sha1($sourceRole), 0, 7);
        }

        if (! isset($this->usedShortRoles[$candidate])) {
            $this->usedShortRoles[$candidate] = $sourceRole;

            return $candidate;
        }

        if ($this->usedShortRoles[$candidate] === $sourceRole) {
            return $candidate;
        }

        $suffix = substr(sha1($sourceRole), 0, 4);
        $base = substr($candidate, 0, self::MAX_LENGTH - 5);

        return $base.'_'.$suffix;
    }
}
