<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\FieldRoleRule;
use Illuminate\Database\Seeder;

final class FieldSchemaSeeder extends Seeder
{
    public function run(): void
    {
        $group = FieldGroup::query()->updateOrCreate(
            ['key' => 'applications'],
            [
                'title' => 'Applications',
                'slug' => 'applications',
                'sort_order' => 1,
                'status' => 'active',
                'legacy_group_key' => 'group_5654f5590ab60',
            ],
        );

        $fields = [
            [
                'key' => 'lead_stage',
                'name' => 'Lead Stage',
                'type' => 'select',
                'storage' => 'column',
                'maps_to_column' => 'lead_stage',
                'config' => ['choices' => ['1' => 'Pre-Disclosure', '2' => 'Disclosed', '3' => 'Finalized']],
            ],
            [
                'key' => 'lead_source',
                'name' => 'Lead Source',
                'type' => 'text',
                'storage' => 'column',
                'maps_to_column' => 'lead_source',
            ],
            [
                'key' => 'internal_margin_notes',
                'name' => 'Internal Margin Notes',
                'type' => 'textarea',
                'storage' => 'field_value',
            ],
        ];

        foreach ($fields as $index => $definition) {
            $field = Field::query()->updateOrCreate(
                [
                    'field_group_id' => $group->id,
                    'key' => $definition['key'],
                ],
                [
                    'entity' => 'lead',
                    'name' => $definition['name'],
                    'type' => $definition['type'],
                    'storage' => $definition['storage'],
                    'maps_to_column' => $definition['maps_to_column'] ?? null,
                    'config' => $definition['config'] ?? null,
                    'sort_order' => $index + 1,
                    'required' => false,
                    'is_filterable' => in_array($definition['key'], ['lead_stage', 'lead_source'], true),
                    'status' => 'active',
                ],
            );

            if ($definition['key'] === 'internal_margin_notes') {
                FieldRoleRule::query()->updateOrCreate(
                    ['field_id' => $field->id, 'role' => 'lead_owner'],
                    ['permission' => 'hidden'],
                );
                FieldRoleRule::query()->updateOrCreate(
                    ['field_id' => $field->id, 'role' => 'franchisor'],
                    ['permission' => 'write'],
                );
            }
        }

        $contactGroup = FieldGroup::query()->updateOrCreate(
            ['key' => 'contact_profile'],
            [
                'title' => 'Contact profile',
                'slug' => 'contact-profile',
                'sort_order' => 2,
                'status' => 'active',
            ],
        );

        Field::query()->updateOrCreate(
            [
                'field_group_id' => $contactGroup->id,
                'key' => 'contact_notes',
            ],
            [
                'entity' => 'contact',
                'name' => 'Contact notes',
                'type' => 'textarea',
                'storage' => 'field_value',
                'sort_order' => 1,
                'required' => false,
                'is_filterable' => false,
                'status' => 'active',
            ],
        );
    }
}
