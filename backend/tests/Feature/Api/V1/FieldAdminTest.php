<?php

declare(strict_types=1);
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
function fieldAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}
function group(): FieldGroup
{
    return FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'slug' => 'applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);
}
test('admin can create a text field', function () {
    $group = group();

    $this->actingAs(fieldAdminUser())
        ->postJson('/api/v1/fields', [
            'field_group_id' => $group->id,
            'entity' => 'lead',
            'key' => 'referral_notes',
            'name' => 'Referral Notes',
            'type' => 'text',
        ])
        ->assertCreated()
        ->assertJsonPath('data.key', 'referral_notes')
        ->assertJsonPath('data.storage', 'field_value');

    $this->assertDatabaseHas('fields', ['key' => 'referral_notes', 'entity' => 'lead']);
});
test('admin can create a relational field', function () {
    $group = group();

    $this->actingAs(fieldAdminUser())
        ->postJson('/api/v1/fields', [
            'field_group_id' => $group->id,
            'entity' => 'lead',
            'key' => 'preferred_areas',
            'name' => 'Preferred Areas',
            'type' => 'relation_many',
            'config' => ['related_entity' => 'area'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'relation_many')
        ->assertJsonPath('data.config.related_entity', 'area');
});
test('relational field requires valid related entity', function () {
    $group = group();

    $this->actingAs(fieldAdminUser())
        ->postJson('/api/v1/fields', [
            'field_group_id' => $group->id,
            'entity' => 'lead',
            'key' => 'bad_relation',
            'name' => 'Bad Relation',
            'type' => 'relation_one',
            'config' => ['related_entity' => 'not_a_table'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['config.related_entity']);
});
test('select field requires choices', function () {
    $group = group();

    $this->actingAs(fieldAdminUser())
        ->postJson('/api/v1/fields', [
            'field_group_id' => $group->id,
            'entity' => 'lead',
            'key' => 'temperature',
            'name' => 'Temperature',
            'type' => 'select',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['config.choices']);
});
test('admin can reorder fields', function () {
    $group = group();
    $first = Field::query()->create([
        'field_group_id' => $group->id, 'entity' => 'lead', 'key' => 'a', 'name' => 'A',
        'type' => 'text', 'storage' => 'field_value', 'sort_order' => 1, 'status' => 'active',
    ]);
    $second = Field::query()->create([
        'field_group_id' => $group->id, 'entity' => 'lead', 'key' => 'b', 'name' => 'B',
        'type' => 'text', 'storage' => 'field_value', 'sort_order' => 2, 'status' => 'active',
    ]);

    $this->actingAs(fieldAdminUser())
        ->postJson('/api/v1/fields/reorder', [
            'items' => [
                ['id' => $first->id, 'sort_order' => 5],
                ['id' => $second->id, 'sort_order' => 1],
            ],
        ])
        ->assertOk();

    expect($first->refresh()->sort_order)->toBe(5);
    expect($second->refresh()->sort_order)->toBe(1);
});
test('admin can delete a field', function () {
    $group = group();
    $field = Field::query()->create([
        'field_group_id' => $group->id, 'entity' => 'lead', 'key' => 'gone', 'name' => 'Gone',
        'type' => 'text', 'storage' => 'field_value', 'sort_order' => 1, 'status' => 'active',
    ]);

    $this->actingAs(fieldAdminUser())
        ->deleteJson("/api/v1/fields/{$field->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('fields', ['id' => $field->id]);
});
test('non manager cannot create fields', function () {
    $group = group();
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->postJson('/api/v1/fields', [
            'field_group_id' => $group->id,
            'entity' => 'lead',
            'key' => 'nope',
            'name' => 'Nope',
            'type' => 'text',
        ])
        ->assertForbidden();
});
test('field groups context widget returns only application and user groups', function () {
    $applications = group();
    $userGroup = FieldGroup::query()->create([
        'key' => 'user',
        'title' => 'User',
        'slug' => 'user',
        'sort_order' => 2,
        'status' => 'active',
    ]);
    FieldGroup::query()->create([
        'key' => 'internal_ops',
        'title' => 'Internal ops',
        'slug' => 'internal-ops',
        'sort_order' => 3,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $applications->id,
        'entity' => 'lead',
        'key' => 'lead_source',
        'name' => 'Lead Source',
        'type' => 'text',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
        'config' => ['widget_eligible' => true],
    ]);
    Field::query()->create([
        'field_group_id' => $userGroup->id,
        'entity' => 'lead',
        'key' => 'referral_notes',
        'name' => 'Referral notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
        'config' => ['widget_eligible' => true],
    ]);

    $response = $this->actingAs(fieldAdminUser())
        ->getJson('/api/v1/field-groups?entity=lead&context=widget')
        ->assertOk();

    $keys = collect($response->json('data'))->pluck('key')->all();

    expect($keys)->toEqual(['applications', 'user']);
});
