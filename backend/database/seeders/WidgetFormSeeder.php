<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\WidgetForm;
use Illuminate\Database\Seeder;

final class WidgetFormSeeder extends Seeder
{
    public function run(): void
    {
        $group = FieldGroup::query()->firstOrCreate(
            ['key' => 'applications'],
            ['title' => 'Applications', 'slug' => 'applications', 'sort_order' => 1, 'status' => 'active'],
        );

        $marketField = Field::query()->updateOrCreate(
            ['field_group_id' => $group->id, 'key' => 'preferred_market'],
            [
                'entity' => 'lead',
                'name' => 'Preferred Market',
                'type' => 'text',
                'storage' => 'field_value',
                'sort_order' => 10,
                'required' => false,
                'status' => 'active',
            ],
        );

        $form = WidgetForm::query()->updateOrCreate(
            ['key' => 'lead_short'],
            [
                'name' => 'Short lead form',
                'site_key' => 'pk_dev',
                'entity' => 'lead',
                'version' => 1,
                'status' => 'active',
            ],
        );

        $form->formFields()->updateOrCreate(
            ['field_id' => $marketField->id],
            ['sort_order' => 0, 'label_override' => 'Preferred market', 'status' => 'active'],
        );
    }
}
