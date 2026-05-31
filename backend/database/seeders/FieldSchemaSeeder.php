<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\FieldRoleRule;
use App\Services\Fields\SystemFieldService;
use App\Support\Fields\ApplicationFieldCatalog;
use Illuminate\Database\Seeder;

final class FieldSchemaSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LegacyAcfFieldSeeder::class);

        app(SystemFieldService::class)->ensureSystemFields();
        $this->ensureInternalMarginNotesField();
        $this->applyInternalMarginNotesRules();
        $this->deactivateMisassignedFields();
    }

    private function ensureInternalMarginNotesField(): void
    {
        $applications = FieldGroup::query()->firstOrCreate(
            ['key' => 'applications'],
            [
                'title' => 'Applications',
                'slug' => 'applications',
                'sort_order' => 1,
                'status' => 'active',
                'legacy_group_key' => 'group_5654f5590ab60',
            ],
        );

        Field::query()->updateOrCreate(
            [
                'field_group_id' => $applications->id,
                'key' => 'internal_margin_notes',
            ],
            [
                'entity' => 'lead',
                'name' => 'Internal Margin Notes',
                'type' => 'textarea',
                'storage' => 'field_value',
                'sort_order' => 500,
                'required' => false,
                'is_filterable' => false,
                'status' => 'active',
            ],
        );
    }

    private function applyInternalMarginNotesRules(): void
    {
        $marginField = Field::query()->where('key', 'internal_margin_notes')->first();

        if ($marginField === null) {
            return;
        }

        FieldRoleRule::query()->updateOrCreate(
            ['field_id' => $marginField->id, 'role' => 'lead_owner'],
            ['permission' => 'hidden'],
        );
        FieldRoleRule::query()->updateOrCreate(
            ['field_id' => $marginField->id, 'role' => 'franchisor'],
            ['permission' => 'write'],
        );
    }

    private function deactivateMisassignedFields(): void
    {
        /** @var list<string> $excluded */
        $excluded = config('fil-legacy-acf.excluded_lead_field_keys', ApplicationFieldCatalog::excludedLeadFieldKeys());

        Field::query()
            ->where('entity', 'lead')
            ->whereIn('key', $excluded)
            ->update(['status' => 'inactive']);

        $allowedGroupKeys = [
            'applications',
            'user',
            'units',
            'locations',
            'areas',
            'organizations',
            'private-notes',
            'administrative-notes',
            'contact_profile',
        ];

        $staleGroupIds = FieldGroup::query()
            ->whereNotIn('key', $allowedGroupKeys)
            ->pluck('id');

        if ($staleGroupIds->isNotEmpty()) {
            Field::query()->whereIn('field_group_id', $staleGroupIds)->update(['status' => 'inactive']);
            FieldGroup::query()->whereIn('id', $staleGroupIds)->update(['status' => 'inactive']);
        }
    }
}
