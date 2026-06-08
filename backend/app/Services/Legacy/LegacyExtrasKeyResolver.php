<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Field;
use App\Support\Fields\FieldTypes;
use Illuminate\Support\Collection;

final class LegacyExtrasKeyResolver
{
    public function __construct(
        private readonly LegacyAcfMetaKeyCatalog $catalog,
    ) {}

    /**
     * @param  Collection<string, Field>  $fieldIndex
     */
    public function resolve(string $metaKey, Collection $fieldIndex, string $entityType = ''): ?LegacyExtrasResolvedKey
    {
        if ($this->shouldDiscard($metaKey, $entityType)) {
            return LegacyExtrasResolvedKey::discard();
        }

        if ($this->relationColumnTarget($entityType, $metaKey) !== null) {
            return LegacyExtrasResolvedKey::relationColumn($metaKey);
        }

        $alias = $this->aliasFor($metaKey);

        if ($alias !== null && $fieldIndex->has($alias)) {
            return LegacyExtrasResolvedKey::scalar($alias);
        }

        $normalized = $this->catalog->normalizeMetaKey($metaKey);

        if ($fieldIndex->has($normalized)) {
            return LegacyExtrasResolvedKey::scalar($normalized);
        }

        if ($fieldIndex->has($metaKey)) {
            return LegacyExtrasResolvedKey::scalar($metaKey);
        }

        foreach ($this->repeaterFieldKeys($fieldIndex) as $repeaterKey) {
            $pattern = '/^'.preg_quote($repeaterKey, '/').'_(\d+)_(.+)$/';

            if (preg_match($pattern, $normalized, $matches) === 1) {
                return LegacyExtrasResolvedKey::repeaterRow(
                    $repeaterKey,
                    (int) $matches[1],
                    (string) $matches[2],
                );
            }
        }

        foreach ($this->parentFieldKeys($fieldIndex) as $parentKey) {
            $prefix = $parentKey.'_';

            if (! str_starts_with($normalized, $prefix)) {
                continue;
            }

            $suffix = substr($normalized, strlen($prefix));

            if ($suffix === false || $suffix === '') {
                continue;
            }

            if (preg_match('/^(.+)_(\d+)_(.+)$/', $suffix, $matches) === 1) {
                return LegacyExtrasResolvedKey::nestedRepeaterRow(
                    $parentKey,
                    (string) $matches[1],
                    (int) $matches[2],
                    (string) $matches[3],
                );
            }
        }

        foreach ($this->catalog->groupPrefixes() as $prefix) {
            if (! str_starts_with($normalized, $prefix)) {
                continue;
            }

            $candidate = substr($normalized, strlen($prefix));

            if ($candidate === false || $candidate === '') {
                continue;
            }

            if ($fieldIndex->has($candidate)) {
                return LegacyExtrasResolvedKey::scalar($candidate);
            }
        }

        return null;
    }

    /**
     * @param  Collection<string, Field>  $fieldIndex
     * @return list<string>
     */
    private function repeaterFieldKeys(Collection $fieldIndex): array
    {
        return $fieldIndex
            ->filter(static function (Field $field): bool {
                /** @var array<string, mixed>|null $config */
                $config = $field->config;

                return ($config['legacy_acf_type'] ?? null) === 'repeater'
                    || FieldTypes::isRepeater((string) $field->type);
            })
            ->keys()
            ->sortByDesc(static fn (string $key): int => strlen($key))
            ->values()
            ->all();
    }

    private function shouldDiscard(string $metaKey, string $entityType): bool
    {
        $normalized = $this->catalog->normalizeMetaKey($metaKey);

        /** @var list<string> $keys */
        $keys = config('fil-legacy-acf.extras_discard_keys', []);

        foreach ($keys as $discardKey) {
            if ($metaKey === $discardKey || $normalized === $this->catalog->normalizeMetaKey($discardKey)) {
                return true;
            }
        }

        /** @var list<string> $prefixes */
        $prefixes = config('fil-legacy-acf.extras_discard_prefixes', []);

        foreach ($prefixes as $prefix) {
            if (str_starts_with($normalized, $this->catalog->normalizeMetaKey($prefix))) {
                return true;
            }
        }

        if ($entityType !== '') {
            /** @var array<string, list<string>> $byEntity */
            $byEntity = config('fil-legacy-acf.extras_discard_keys_by_entity', []);
            $entityKeys = $byEntity[$entityType] ?? [];

            foreach ($entityKeys as $discardKey) {
                if ($metaKey === $discardKey || $normalized === $this->catalog->normalizeMetaKey($discardKey)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{relation: string, column: string}|null
     */
    public function relationColumnTarget(string $entityType, string $metaKey): ?array
    {
        /** @var array<string, array<string, array{relation: string, column: string}>> $map */
        $map = config('fil-legacy-acf.extras_relation_columns', []);
        $normalized = $this->catalog->normalizeMetaKey($metaKey);

        return $map[$entityType][$metaKey]
            ?? $map[$entityType][$normalized]
            ?? null;
    }

    /**
     * @param  Collection<string, Field>  $fieldIndex
     * @return list<string>
     */
    private function parentFieldKeys(Collection $fieldIndex): array
    {
        return $fieldIndex
            ->keys()
            ->sortByDesc(static fn (string $key): int => strlen($key))
            ->values()
            ->all();
    }

    private function aliasFor(string $metaKey): ?string
    {
        /** @var array<string, string> $aliases */
        $aliases = config('fil-legacy-acf.extras_key_aliases', []);

        return $aliases[$metaKey] ?? $aliases[$this->catalog->normalizeMetaKey($metaKey)] ?? null;
    }
}
