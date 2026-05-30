<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Lead;
use App\Models\User;
use App\Models\WidgetForm;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WidgetFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function leadField(string $key, string $type = 'text', bool $required = false): Field
    {
        $group = FieldGroup::query()->firstOrCreate(
            ['key' => 'applications'],
            ['title' => 'Applications', 'sort_order' => 1, 'status' => 'active'],
        );

        return Field::query()->create([
            'field_group_id' => $group->id,
            'entity' => 'lead',
            'key' => $key,
            'name' => ucfirst($key),
            'type' => $type,
            'storage' => 'field_value',
            'required' => $required,
            'sort_order' => 1,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_widget_form_and_sync_fields(): void
    {
        $field = $this->leadField('referral_notes');

        $admin = $this->admin();

        $formId = $this->actingAs($admin)
            ->postJson('/api/v1/widget-forms', [
                'key' => 'lead_short',
                'name' => 'Short lead form',
                'site_key' => 'pk_dev',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($admin)
            ->putJson("/api/v1/widget-forms/{$formId}/fields", [
                'fields' => [
                    ['field_id' => $field->id, 'sort_order' => 0, 'label_override' => 'Notes'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.fields.0.field.key', 'referral_notes')
            ->assertJsonPath('data.fields.0.label_override', 'Notes');

        $this->assertDatabaseHas('widget_form_fields', [
            'widget_form_id' => $formId,
            'field_id' => $field->id,
        ]);
    }

    public function test_public_form_config_returns_widget_fields(): void
    {
        $field = $this->leadField('referral_notes');
        $form = WidgetForm::query()->create([
            'key' => 'lead_short', 'name' => 'Short', 'site_key' => 'pk_dev', 'status' => 'active',
        ]);
        $form->formFields()->create(['field_id' => $field->id, 'sort_order' => 0, 'status' => 'active']);

        $this->getJson('/api/public/v1/form-config?site_key=pk_dev')
            ->assertOk()
            ->assertJsonPath('data.form_key', 'lead_short')
            ->assertJsonPath('data.fields.0.key', 'referral_notes')
            ->assertJsonPath('data.theme', null);
    }

    public function test_public_form_config_returns_theme_from_widget_settings(): void
    {
        WidgetForm::query()->create([
            'key' => 'lead_short',
            'name' => 'Short',
            'site_key' => 'pk_dev',
            'status' => 'active',
            'settings' => [
                'theme' => [
                    'primary' => '#0f766e',
                    'error' => '#b91c1c',
                ],
            ],
        ]);

        $this->getJson('/api/public/v1/form-config?site_key=pk_dev')
            ->assertOk()
            ->assertJsonPath('data.theme.primary', '#0f766e')
            ->assertJsonPath('data.theme.error', '#b91c1c');
    }

    public function test_public_intake_persists_custom_field_values(): void
    {
        $field = $this->leadField('referral_notes');
        $form = WidgetForm::query()->create([
            'key' => 'lead_short', 'name' => 'Short', 'site_key' => 'pk_dev', 'status' => 'active',
        ]);
        $form->formFields()->create(['field_id' => $field->id, 'sort_order' => 0, 'status' => 'active']);

        $this->postJson('/api/public/v1/leads', [
            'site_key' => 'pk_dev',
            'email' => 'prospect@example.com',
            'first_name' => 'Pat',
            'last_name' => 'Lee',
            'custom' => ['referral_notes' => 'Met at expo'],
        ])->assertCreated();

        $lead = Lead::query()->firstOrFail();

        $this->assertDatabaseHas('field_values', [
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'field_id' => $field->id,
            'value_text' => 'Met at expo',
        ]);
    }

    public function test_widget_form_management_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        $this->actingAs($user)
            ->postJson('/api/v1/widget-forms', ['key' => 'x', 'name' => 'X'])
            ->assertForbidden();
    }
}
