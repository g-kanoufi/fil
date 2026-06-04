<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('store opening checklist seeds defaults and accepts updates', function () {
    $staff = User::factory()->create();
    $staff->assignRole('franchisor');

    $store = Store::factory()->create();

    $this->actingAs($staff)
        ->getJson("/api/v1/stores/{$store->id}/opening-checklist")
        ->assertOk()
        ->assertJsonCount(5, 'data.items')
        ->assertJsonPath('data.items.0.key', 'site_selected');

    $this->actingAs($staff)
        ->patchJson("/api/v1/stores/{$store->id}/opening-checklist", [
            'items' => [
                ['key' => 'site_selected', 'completed' => true, 'notes' => 'Downtown pad'],
            ],
            'buildout_started_at' => '2026-05-01',
            'expected_opening_at' => '2026-08-01',
        ])
        ->assertOk()
        ->assertJsonPath('data.timeline.buildout_started_at', '2026-05-01')
        ->assertJsonPath('data.items.0.notes', 'Downtown pad');

    expect($store->fresh()?->buildout_started_at?->toDateString())->toBe('2026-05-01');
});

test('closing update syncs linked documents', function () {
    $staff = User::factory()->create();
    $staff->assignRole('franchisor');

    $closing = Closing::factory()->create();
    $document = Document::query()->create([
        'title' => 'Franchise agreement',
        'slug' => 'franchise-agreement-test',
        'mime_type' => 'application/pdf',
        'storage_disk' => 'local',
        'storage_path' => 'tests/agreement.pdf',
        'status' => 'active',
    ]);

    $this->actingAs($staff)
        ->patchJson("/api/v1/closings/{$closing->id}", [
            'document_ids' => [$document->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.documents.0.id', $document->id);

    expect(DocumentLink::query()
        ->where('linkable_type', Closing::class)
        ->where('linkable_id', $closing->id)
        ->count())->toBe(1);
});
