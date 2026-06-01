<?php

declare(strict_types=1);
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Lead;
use App\Models\User;
use App\Models\WidgetForm;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
function widgetAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}
function leadField(string $key, string $type = 'text', bool $required = false): Field
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
        'config' => ['widget_eligible' => true],
    ]);
}
test('admin can create widget form and sync fields', function () {
    $field = leadField('referral_notes');

    $admin = widgetAdminUser();

    $createResponse = $this->actingAs($admin)
        ->postJson('/api/v1/widget-forms', [
            'key' => 'lead_short',
            'name' => 'Short lead form',
        ])
        ->assertCreated();

    $formId = $createResponse->json('data.id');
    $siteKey = $createResponse->json('data.site_key');

    expect($siteKey)->toBeString()->toStartWith('pk_live_');

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
});
test('public form config returns widget fields', function () {
    $field = leadField('referral_notes');
    $form = WidgetForm::query()->create([
        'key' => 'lead_short', 'name' => 'Short', 'site_key' => 'pk_dev', 'status' => 'active',
    ]);
    $form->formFields()->create(['field_id' => $field->id, 'sort_order' => 0, 'status' => 'active']);

    $this->getJson('/api/public/v1/form-config?site_key=pk_dev')
        ->assertOk()
        ->assertJsonPath('data.form_key', 'lead_short')
        ->assertJsonPath('data.fields.0.key', 'referral_notes')
        ->assertJsonPath('data.theme', null);
});
test('public form config returns theme from widget settings', function () {
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
});
test('public intake persists custom field values', function () {
    $field = leadField('referral_notes');
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
});
test('widget form management requires permission', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->postJson('/api/v1/widget-forms', ['key' => 'x', 'name' => 'X'])
        ->assertForbidden();
});
test('widget form sync rejects fields outside application and user groups', function () {
    $allowed = leadField('referral_notes');

    $internalGroup = FieldGroup::query()->create([
        'key' => 'internal_ops',
        'title' => 'Internal ops',
        'sort_order' => 99,
        'status' => 'active',
    ]);
    $blocked = Field::query()->create([
        'field_group_id' => $internalGroup->id,
        'entity' => 'lead',
        'key' => 'internal_margin_notes',
        'name' => 'Internal Margin Notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $admin = widgetAdminUser();
    $formId = $this->actingAs($admin)
        ->postJson('/api/v1/widget-forms', ['key' => 'lead_short', 'name' => 'Short'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($admin)
        ->putJson("/api/v1/widget-forms/{$formId}/fields", [
            'fields' => [
                ['field_id' => $blocked->id, 'sort_order' => 0],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fields.0.field_id']);

    $this->actingAs($admin)
        ->putJson("/api/v1/widget-forms/{$formId}/fields", [
            'fields' => [
                ['field_id' => $allowed->id, 'sort_order' => 0],
            ],
        ])
        ->assertOk();
});
test('admin can rotate widget form site key', function () {
    $admin = widgetAdminUser();

    $form = WidgetForm::query()->create([
        'key' => 'lead_short',
        'name' => 'Short',
        'site_key' => 'pk_live_oldkey123456789012345678',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/widget-forms/{$form->id}/rotate-site-key")
        ->assertOk();

    $newKey = $response->json('data.site_key');

    expect($newKey)->toBeString()
        ->toStartWith('pk_live_')
        ->not->toBe('pk_live_oldkey123456789012345678');

    $this->assertDatabaseHas('widget_forms', [
        'id' => $form->id,
        'site_key' => $newKey,
    ]);
});

function contactField(string $key, string $type = 'text'): Field
{
    $group = FieldGroup::query()->firstOrCreate(
        ['key' => 'user'],
        ['title' => 'User', 'sort_order' => 2, 'status' => 'active'],
    );

    return Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'contact',
        'key' => $key,
        'name' => ucfirst(str_replace('_', ' ', $key)),
        'type' => $type,
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
        'config' => ['widget_eligible' => true],
    ]);
}

test('widget form sync accepts user-group contact fields', function () {
    $field = contactField('linkedin_url');

    $admin = widgetAdminUser();
    $formId = $this->actingAs($admin)
        ->postJson('/api/v1/widget-forms', ['key' => 'lead_full', 'name' => 'Full'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($admin)
        ->putJson("/api/v1/widget-forms/{$formId}/fields", [
            'fields' => [
                ['field_id' => $field->id, 'sort_order' => 0],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.fields.0.field.key', 'linkedin_url');
});

test('public intake persists contact custom fields on prospect user', function () {
    $field = contactField('linkedin_url');
    $form = WidgetForm::query()->create([
        'key' => 'lead_full', 'name' => 'Full', 'site_key' => 'pk_dev_contact', 'status' => 'active',
    ]);
    $form->formFields()->create(['field_id' => $field->id, 'sort_order' => 0, 'status' => 'active']);

    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_dev_contact',
        'email' => 'prospect-contact@example.com',
        'first_name' => 'Sam',
        'last_name' => 'Ng',
        'custom' => ['linkedin_url' => 'https://linkedin.com/in/samng'],
    ])->assertCreated();

    $lead = Lead::query()->firstOrFail();
    $prospectId = $lead->prospect_user_id;

    expect($prospectId)->not->toBeNull();

    $this->assertDatabaseHas('field_values', [
        'entity_type' => 'contact',
        'entity_id' => $prospectId,
        'field_id' => $field->id,
        'value_text' => 'https://linkedin.com/in/samng',
    ]);
});
