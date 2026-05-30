<?php

declare(strict_types=1);
use App\Models\Document;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('franchisor can list and show documents', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $document = Document::query()->create([
        'title' => 'Unit FDD',
        'mime_type' => 'application/pdf',
        'storage_disk' => 'local',
        'storage_path' => 'fdd/unit.pdf',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/documents')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Unit FDD');

    $this->actingAs($user)
        ->getJson("/api/v1/documents/{$document->id}")
        ->assertOk()
        ->assertJsonPath('data.download_url', "/documents/{$document->id}/download");
});
test('lead owner can list documents', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    Document::query()->create([
        'title' => 'Store agreement',
        'mime_type' => 'application/pdf',
        'storage_disk' => 'local',
        'storage_path' => 'docs/agreement.pdf',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/documents')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Store agreement');
});
