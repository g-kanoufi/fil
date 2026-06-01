<?php

declare(strict_types=1);

namespace App\Support\Legacy;

use App\Models\Field;
use Illuminate\Support\Collection;

/**
 * Expected FIL field keys from bundled legacy ACF JSON (before DB import).
 */
final class LegacyBundledAcfFieldCatalog
{
    public function __construct(
        private readonly LegacyAcfGroupRegistry $groups,
    ) {}

    /**
     * @return Collection<string, Field>
     */
    public function fieldIndexForEntity(string $entity): Collection
    {
        /** @var Collection<string, Field> $index */
        $index = collect();

        foreach (glob(base_path('resources/legacy-acf/*.json')) ?: [] as $path) {
            $json = json_decode((string) file_get_contents($path), true);

            if (! is_array($json) || ! isset($json['key'], $json['title'])) {
                continue;
            }

            $meta = $this->groups->resolve(
                (string) $json['key'],
                (string) $json['title'],
                $json['location'] ?? [],
            );

            if ($meta === null || $meta['entity'] !== $entity) {
                continue;
            }

            $this->collectFields(
                (array) ($json['fields'] ?? []),
                $entity,
                $index,
            );
        }

        return $index;
    }

    /**
     * @param  list<array<string, mixed>>  $acfFields
     * @param  Collection<string, Field>  $index
     */
    private function collectFields(array $acfFields, string $entity, Collection $index): void
    {
        foreach ($acfFields as $acfField) {
            if (! is_array($acfField) || empty($acfField['type'])) {
                continue;
            }

            $type = (string) $acfField['type'];
            $name = (string) ($acfField['name'] ?? '');

            if ($name === '' || in_array($name, config('fil-legacy-acf.skip_field_keys', []), true)) {
                continue;
            }

            if (in_array($type, config('fil-legacy-acf.skip_field_types', []), true)) {
                continue;
            }

            if ($entity === 'lead' && in_array($name, config('fil-legacy-acf.excluded_lead_field_keys', []), true)) {
                continue;
            }

            if (! $index->has($name)) {
                $field = new Field([
                    'key' => $name,
                    'entity' => $entity,
                    'config' => ['legacy_acf_type' => $type],
                ]);
                $index->put($name, $field);
            }

            if (in_array($type, ['repeater', 'group'], true) && is_array($acfField['sub_fields'] ?? null)) {
                $this->collectFields($acfField['sub_fields'], $entity, $index);
            }

            if ($type === 'flexible_content' && is_array($acfField['layouts'] ?? null)) {
                foreach ($acfField['layouts'] as $layout) {
                    if (! is_array($layout) || ! is_array($layout['sub_fields'] ?? null)) {
                        continue;
                    }

                    $this->collectFields($layout['sub_fields'], $entity, $index);
                }
            }
        }
    }
}
