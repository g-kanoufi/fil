<?php

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can view contact detail', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('franchisor');

    $contact = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);
    $contact->assignRole('lead_owner');

    $this->actingAs($viewer)
        ->getJson("/api/v1/contacts/{$contact->id}")
        ->assertOk()
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonPath('data.first_name', 'Jane')
        ->assertJsonPath('data.roles.0', 'lead_owner');
});

test('staff without contacts permission cannot view contact detail', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['app.access', 'leads.view']);

    $contact = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/v1/contacts/{$contact->id}")
        ->assertForbidden();
});

test('franchisor can update contact custom fields', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('franchisor');

    $contact = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);
    $contact->assignRole('lead_owner');

    $group = FieldGroup::query()->create([
        'key' => 'contact_profile',
        'title' => 'Contact profile',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $field = Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'contact',
        'key' => 'contact_notes',
        'name' => 'Contact notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $this->actingAs($viewer)
        ->patchJson("/api/v1/contacts/{$contact->id}", [
            'custom' => ['contact_notes' => 'Prefers morning calls'],
        ])
        ->assertOk()
        ->assertJsonPath('data.custom.contact_notes', 'Prefers morning calls');

    $this->assertDatabaseHas('field_values', [
        'entity_type' => 'contact',
        'entity_id' => $contact->id,
        'field_id' => $field->id,
        'value_text' => 'Prefers morning calls',
    ]);

    $this->assertDatabaseHas('activity_events', [
        'category' => 'contact',
        'action' => 'updated',
        'subject_type' => 'user',
        'subject_id' => $contact->id,
    ]);
});

test('staff without contacts manage cannot update contact custom fields', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['app.access', 'contacts.view']);

    $contact = User::factory()->create();
    $contact->assignRole('lead_owner');

    $this->actingAs($user)
        ->patchJson("/api/v1/contacts/{$contact->id}", [
            'custom' => ['contact_notes' => 'Should not save'],
        ])
        ->assertForbidden();
});

test('prospect-only user cannot be viewed as contact', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('franchisor');

    $prospect = User::factory()->create([
        'email' => 'prospect-only@example.com',
    ]);
    $prospect->assignRole('prospect');

    $this->actingAs($viewer)
        ->getJson("/api/v1/contacts/{$prospect->id}")
        ->assertForbidden();
});
