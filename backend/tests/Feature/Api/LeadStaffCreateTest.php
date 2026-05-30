<?php

declare(strict_types=1);
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('franchisor can create lead', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->postJson('/api/v1/leads', [
            'title' => 'New Expo Lead',
            'lead_source' => 'expo',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'New Expo Lead')
        ->assertJsonPath('data.lead_source', 'expo');

    $this->assertDatabaseHas('leads', ['title' => 'New Expo Lead']);
    $this->assertDatabaseHas('activity_events', [
        'category' => 'lead',
        'action' => 'created',
    ]);
});
test('lead owner can update custom fields', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $field = Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'referral_notes',
        'name' => 'Referral notes',
        'type' => 'text',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $lead = Lead::factory()->create(['title' => 'Custom Field Lead']);

    $this->actingAs($user)
        ->patchJson("/api/v1/leads/{$lead->id}", [
            'custom' => ['referral_notes' => 'Met at booth 12'],
        ])
        ->assertOk()
        ->assertJsonPath('data.custom.referral_notes', 'Met at booth 12');

    $this->assertDatabaseHas('field_values', [
        'entity_type' => 'lead',
        'entity_id' => $lead->id,
        'field_id' => $field->id,
        'value_text' => 'Met at booth 12',
    ]);
});
test('franchisor can convert lead to store', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $lead = Lead::factory()->create(['title' => 'Convert Me']);

    $this->actingAs($user)
        ->postJson("/api/v1/leads/{$lead->id}/convert")
        ->assertCreated()
        ->assertJsonPath('data.name', 'Convert Me');

    $lead->refresh();
    expect($lead->status)->toBe('converted');

    $this->assertDatabaseHas('activity_events', [
        'category' => 'lead',
        'action' => 'converted',
    ]);
});
