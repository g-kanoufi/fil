<?php

declare(strict_types=1);

namespace App\Support\Legacy;

use Illuminate\Support\Str;

final class LegacyAcfGroupRegistry
{
    /**
     * @param  array<string, mixed>  $variantOverride
     * @return array{import: bool, key: string, title: string, entity: string, legacy_post_type: string, sort_order: int, merge_into?: string}|null
     */
    public function resolve(
        string $legacyGroupKey,
        string $acfTitle,
        array $locationRules = [],
        ?string $legacyPostType = null,
        array $variantOverride = [],
    ): ?array {
        /** @var array<string, array<string, mixed>> $configured */
        $configured = config('fil-legacy-acf.groups', []);

        if (isset($configured[$legacyGroupKey])) {
            $meta = array_replace($configured[$legacyGroupKey], $variantOverride);

            if (($meta['import'] ?? true) === false) {
                return null;
            }

            $resolvedPostType = (string) ($meta['legacy_post_type'] ?? $legacyPostType ?? $this->inferLegacyPostType($locationRules) ?? '');

            return [
                'import' => true,
                'key' => (string) ($meta['merge_into'] ?? $meta['key'] ?? Str::slug($acfTitle)),
                'title' => (string) ($meta['title'] ?? $acfTitle),
                'entity' => (string) ($meta['entity'] ?? $this->inferEntityForPostType($resolvedPostType) ?? $this->inferEntity($locationRules) ?? 'lead'),
                'legacy_post_type' => $resolvedPostType,
                'sort_order' => (int) ($meta['sort_order'] ?? 100),
                'merge_into' => isset($meta['merge_into']) ? (string) $meta['merge_into'] : null,
            ];
        }

        $entity = $this->inferEntity($locationRules);

        if ($entity === null) {
            return null;
        }

        return [
            'import' => true,
            'key' => Str::slug($acfTitle),
            'title' => $acfTitle,
            'entity' => $entity,
            'legacy_post_type' => $this->inferLegacyPostType($locationRules) ?? '',
            'sort_order' => 100,
            'merge_into' => null,
        ];
    }

    /**
     * @param  list<list<array<string, mixed>>>  $locationRules
     * @return list<string>
     */
    public function postTypesFromLocation(array $locationRules): array
    {
        $types = [];

        foreach ($locationRules as $ruleSet) {
            foreach ($ruleSet as $rule) {
                $param = (string) ($rule['param'] ?? '');

                if ($param === 'post_type') {
                    $value = (string) ($rule['value'] ?? '');

                    if ($value !== '') {
                        $types[] = $value;
                    }
                }

                if ($param === 'user_form') {
                    $types[] = 'user';
                }
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * @param  list<list<array<string, mixed>>>  $locationRules
     */
    public function inferLegacyPostType(array $locationRules): ?string
    {
        /** @var array<string, string> $map */
        $map = config('fil-legacy-acf.post_type_entity', []);

        foreach ($locationRules as $ruleSet) {
            foreach ($ruleSet as $rule) {
                $param = (string) ($rule['param'] ?? '');

                if ($param === 'post_type') {
                    $postType = (string) ($rule['value'] ?? '');

                    if ($postType !== '' && isset($map[$postType])) {
                        return $postType;
                    }
                }

                if ($param === 'user_form') {
                    return 'user';
                }
            }
        }

        foreach ($locationRules as $ruleSet) {
            foreach ($ruleSet as $rule) {
                if ((string) ($rule['param'] ?? '') === 'post_type') {
                    $postType = (string) ($rule['value'] ?? '');

                    return $postType !== '' ? $postType : null;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<list<array<string, mixed>>>  $locationRules
     */
    private function inferEntityForPostType(string $legacyPostType): ?string
    {
        if ($legacyPostType === '') {
            return null;
        }

        /** @var array<string, string> $map */
        $map = config('fil-legacy-acf.post_type_entity', []);

        return $map[$legacyPostType] ?? null;
    }

    /**
     * @param  list<list<array<string, mixed>>>  $locationRules
     */
    private function inferEntity(array $locationRules): ?string
    {
        /** @var array<string, string> $map */
        $map = config('fil-legacy-acf.post_type_entity', []);

        foreach ($locationRules as $ruleSet) {
            foreach ($ruleSet as $rule) {
                $param = (string) ($rule['param'] ?? '');

                if ($param === 'post_type') {
                    $postType = (string) ($rule['value'] ?? '');

                    if (isset($map[$postType])) {
                        return $map[$postType];
                    }
                }

                if ($param === 'user_form') {
                    $form = 'user_form:'.(string) ($rule['value'] ?? '');

                    if (isset($map[$form])) {
                        return $map[$form];
                    }
                }
            }
        }

        return null;
    }
}
