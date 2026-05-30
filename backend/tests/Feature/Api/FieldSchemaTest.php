<?php

use App\Models\User;
use Database\Seeders\FieldSchemaSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
    $this->seed(FieldSchemaSeeder::class);
});

test('lead owner can fetch lead field schema', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->getJson('/api/v1/fields?entity=lead')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'entity',
                'groups' => [
                    ['id', 'key', 'title', 'fields' => [['key', 'name', 'type']]],
                ],
                'hidden_field_keys',
                'readonly_field_keys',
            ],
        ])
        ->assertJsonPath('data.entity', 'lead')
        ->assertJsonMissingPath('data.groups.0.fields.2');
});

test('session includes field access keys', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->getJson('/api/v1/session')
        ->assertOk()
        ->assertJsonPath('data.hidden_field_keys', ['internal_margin_notes']);
});

test('prospect cannot fetch field schema', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    $this->actingAs($user)
        ->getJson('/api/v1/fields?entity=lead')
        ->assertForbidden();
});
