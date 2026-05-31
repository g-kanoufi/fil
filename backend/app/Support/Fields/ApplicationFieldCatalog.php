<?php

declare(strict_types=1);

namespace App\Support\Fields;

/**
 * Zorzees Applications group (group_5654f5590ab60) tier-1 lead fields.
 *
 * Values align with {@see config/fil-pipeline.php} and PrimeIV site 9 ACF.
 * Store/unit status fields ({@see store_status}, location {@see status}) belong on
 * store entities — never mixed into this catalog.
 */
final class ApplicationFieldCatalog
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function normalizedChoices(array $choices): array
    {
        $normalized = [];

        foreach ($choices as $value => $label) {
            $normalized[] = [
                'value' => (string) $value,
                'label' => (string) $label,
            ];
        }

        return $normalized;
    }

    /**
     * @return list<array{
     *     key: string,
     *     name: string,
     *     type: string,
     *     storage: string,
     *     maps_to_column?: string,
     *     config?: array<string, mixed>,
     *     is_filterable?: bool,
     *     required?: bool,
     * }>
     */
    public static function tierOneFields(): array
    {
        return [
            [
                'key' => 'lead_stage',
                'name' => 'Lead Stage',
                'type' => 'select',
                'storage' => 'column',
                'maps_to_column' => 'lead_stage',
                'config' => [
                    'choices' => self::normalizedChoices([
                        '1' => '1. Pre-Disclosure',
                        '2' => '2. Disclosed',
                        '3' => '3. Finalized',
                    ]),
                ],
                'is_filterable' => true,
            ],
            [
                'key' => 'lead_status',
                'name' => 'Lead Status',
                'type' => 'select',
                'storage' => 'column',
                'maps_to_column' => 'lead_status',
                'config' => ['choices' => self::normalizedChoices(self::leadStatusChoices())],
                'is_filterable' => true,
            ],
            [
                'key' => 'lead_temp',
                'name' => 'Lead Temp',
                'type' => 'select',
                'storage' => 'column',
                'maps_to_column' => 'lead_temp',
                'config' => [
                    'choices' => self::normalizedChoices([
                        'Cold' => 'Cold',
                        'Warm' => 'Warm',
                        'Hot' => 'Hot',
                    ]),
                ],
                'is_filterable' => true,
            ],
            [
                'key' => 'lead_fdd_status',
                'name' => 'FDD Status',
                'type' => 'text',
                'storage' => 'column',
                'maps_to_column' => 'lead_fdd_status',
                'is_filterable' => false,
            ],
            [
                'key' => 'lead_source',
                'name' => 'Lead Source',
                'type' => 'text',
                'storage' => 'column',
                'maps_to_column' => 'lead_source',
                'is_filterable' => true,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function leadStatusChoices(): array
    {
        /** @var array<string, string> $labels */
        $labels = config('fil-pipeline.lead_status_labels', []);

        return $labels;
    }

    /**
     * Keys that must never appear on lead/application forms (store or location helpers).
     *
     * @return list<string>
     */
    public static function excludedLeadFieldKeys(): array
    {
        /** @var list<string> $keys */
        $keys = config('fil-legacy-acf.excluded_lead_field_keys', []);

        return $keys !== [] ? $keys : [
            'status',
            'store_status',
            'open',
            'closed',
        ];
    }
}
