<?php

declare(strict_types=1);

use App\Models\EntityNote;
use App\Models\Lead;
use App\Models\StaffTodo;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
});

test('staff can list and create notes on a lead', function () {
    $staff = User::factory()->create();
    $staff->assignRole('franchisor');

    $lead = Lead::factory()->create();

    $this->actingAs($staff)
        ->getJson("/api/v1/leads/{$lead->id}/notes")
        ->assertOk()
        ->assertJsonPath('data', []);

    $this->actingAs($staff)
        ->postJson("/api/v1/leads/{$lead->id}/notes", [
            'body' => 'Follow up on FDD questions',
            'is_private' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Follow up on FDD questions')
        ->assertJsonPath('data.author.id', $staff->id);

    expect(EntityNote::query()->where('subject_type', 'lead')->count())->toBe(1);
});

test('private notes are hidden from users without private note grant', function () {
    $leadOwner = User::factory()->create();
    $leadOwner->assignRole('lead_owner');

    $franchisor = User::factory()->create();
    $franchisor->assignRole('franchisor');

    $lead = Lead::factory()->create();

    EntityNote::query()->create([
        'subject_type' => 'lead',
        'subject_id' => $lead->id,
        'author_user_id' => $franchisor->id,
        'body' => 'Public note',
        'is_private' => false,
    ]);

    EntityNote::query()->create([
        'subject_type' => 'lead',
        'subject_id' => $lead->id,
        'author_user_id' => $franchisor->id,
        'body' => 'Private corp note',
        'is_private' => true,
    ]);

    $this->actingAs($leadOwner)
        ->getJson("/api/v1/leads/{$lead->id}/notes")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', 'Public note');

    $this->actingAs($franchisor)
        ->getJson("/api/v1/leads/{$lead->id}/notes")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('staff can manage corp todos', function () {
    $staff = User::factory()->create();
    $staff->assignRole('franchisor');

    $store = Store::factory()->create();

    $this->actingAs($staff)
        ->postJson('/api/v1/todos', [
            'title' => 'Schedule inspection',
            'due_at' => '2026-06-15',
            'subject_type' => 'store',
            'subject_id' => $store->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Schedule inspection')
        ->assertJsonPath('data.subject.type', 'store');

    $todo = StaffTodo::query()->firstOrFail();

    $this->actingAs($staff)
        ->patchJson("/api/v1/todos/{$todo->id}", [
            'completed' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.completed', true);

    $this->actingAs($staff)
        ->getJson('/api/v1/todos?completed=0')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('franchisee cannot access corp todos', function () {
    $franchisee = User::factory()->create();
    $franchisee->assignRole('franchisee');

    $this->actingAs($franchisee)
        ->getJson('/api/v1/todos')
        ->assertForbidden();
});
