<?php

declare(strict_types=1);

use App\Models\AchCustomer;
use App\Models\AchFundingSource;
use App\Models\AchTransfer;
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
test('franchisor can load documents browser settings and rows', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $store = Store::factory()->create(['name' => 'Demo Store']);
    $document = Document::query()->create([
        'title' => 'Doctors License',
        'mime_type' => 'application/pdf',
        'storage_disk' => 'local',
        'storage_path' => 'legacy/1/license.pdf',
        'status' => 'active',
    ]);

    DocumentLink::query()->create([
        'document_id' => $document->id,
        'linkable_type' => Store::class,
        'linkable_id' => $store->id,
        'role' => 'doctors_license',
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/documents/settings')
        ->assertOk()
        ->assertJsonPath('data.enabled', true);

    $this->actingAs($user)
        ->getJson('/api/v1/documents/rows?entity=store')
        ->assertOk()
        ->assertJsonPath('data.0.entity_title', 'Demo Store')
        ->assertJsonPath('data.0.doc_type', 'doctors_license');
});

test('franchisor can view store ach enrollment', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $customer = AchCustomer::query()->create([
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'provider' => 'dwolla',
        'external_customer_id' => 'cust-test',
        'status' => 'active',
    ]);

    AchFundingSource::query()->create([
        'ach_customer_id' => $customer->id,
        'external_funding_source_id' => 'fs-test',
        'name' => 'Operating',
        'type' => 'bank',
        'status' => 'active',
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/stores/{$store->id}/ach/customer")
        ->assertOk()
        ->assertJsonPath('data.enrolled', true)
        ->assertJsonPath('data.funding_sources.0.external_funding_source_id', 'fs-test');
});

test('franchisor can request plaid link token stub', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/ach/plaid/link-token")
        ->assertOk()
        ->assertJsonPath('data.store_id', $store->id)
        ->assertJsonStructure(['data' => ['link_token', 'expiration']]);
});

test('dwolla webhook updates transfer status', function () {
    $store = Store::factory()->create();

    $transfer = AchTransfer::query()->create([
        'store_id' => $store->id,
        'transferred_at' => now(),
        'provider' => 'dwolla',
        'provider_status' => 'pending',
        'status' => 0,
        'amount' => 100,
        'external_transfer_id' => 'transfer-123',
    ]);

    $this->postJson('/api/webhooks/dwolla', [
        'topic' => 'transfer_completed',
        'resourceId' => 'transfer-123',
    ])->assertOk();

    expect($transfer->fresh()->provider_status)->toBe('processed');
});
