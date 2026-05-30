<?php

declare(strict_types=1);
use App\Models\Area;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\Store;
use App\Models\User;
use App\Services\Documents\DocumentExportRepository;
use App\Support\MinimalPdf;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});
test('area rep can view and download in scope store document', function () {
    $rep = User::factory()->create();
    $rep->assignRole('area_rep');

    $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
    $store = Store::factory()->create(['area_id' => $area->id]);
    $document = createLinkedDocument($store, 'docs/in-scope.pdf');

    $this->actingAs($rep)
        ->getJson("/api/v1/documents/{$document->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Scoped license');

    $this->actingAs($rep)
        ->get("/api/v1/documents/{$document->id}/download")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
test('area rep cannot view or download out of scope store document', function () {
    $rep = User::factory()->create();
    $rep->assignRole('area_rep');

    Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
    $store = Store::factory()->create(['area_id' => null]);
    $document = createLinkedDocument($store, 'docs/out-of-scope.pdf');

    $this->actingAs($rep)
        ->getJson("/api/v1/documents/{$document->id}")
        ->assertForbidden();

    $this->actingAs($rep)
        ->get("/api/v1/documents/{$document->id}/download")
        ->assertForbidden();
});
test('staff without documents permission cannot access document apis', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['app.access', 'stores.view']);

    $store = Store::factory()->create();
    $document = createLinkedDocument($store, 'docs/restricted.pdf');

    $this->actingAs($user)
        ->getJson('/api/v1/documents')
        ->assertForbidden();

    $this->actingAs($user)
        ->getJson("/api/v1/documents/{$document->id}")
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson('/api/v1/documents/export/start', [
            'entity' => 'store',
            'doc_type' => 'doctors_license',
            'doc_type_label' => 'Doctors License',
        ])
        ->assertForbidden();
});
test('document export status is hidden from other users', function () {
    $owner = User::factory()->create();
    $owner->assignRole('franchisor');

    $other = User::factory()->create();
    $other->assignRole('franchisor');

    $exportId = 'export-scope-test';
    app(DocumentExportRepository::class)->put($exportId, [
        'export_id' => $exportId,
        'user_id' => $owner->id,
        'status' => 'completed',
        'zip_path' => 'exports/scope-test.zip',
    ]);

    $this->actingAs($other)
        ->getJson('/api/v1/documents/export/status?export_id='.$exportId)
        ->assertNotFound()
        ->assertJsonPath('message', 'Export not found.');
});
test('document export download is hidden from other users', function () {
    $owner = User::factory()->create();
    $owner->assignRole('franchisor');

    $other = User::factory()->create();
    $other->assignRole('franchisor');

    $exportId = 'export-download-scope-test';
    Storage::disk('local')->put('exports/scope-test.zip', 'zip-bytes');

    app(DocumentExportRepository::class)->put($exportId, [
        'export_id' => $exportId,
        'user_id' => $owner->id,
        'status' => 'completed',
        'zip_path' => 'exports/scope-test.zip',
    ]);

    $this->actingAs($other)
        ->get(route('document-exports.download', ['exportId' => $exportId]))
        ->assertNotFound();
});
function createLinkedDocument(Store $store, string $storagePath): Document
{
    Storage::disk('local')->put($storagePath, MinimalPdf::generate('Scoped license'));

    $document = Document::query()->create([
        'title' => 'Scoped license',
        'mime_type' => 'application/pdf',
        'storage_disk' => 'local',
        'storage_path' => $storagePath,
        'status' => 'active',
    ]);

    DocumentLink::query()->create([
        'document_id' => $document->id,
        'linkable_type' => Store::class,
        'linkable_id' => $store->id,
        'role' => 'doctors_license',
        'sort_order' => 0,
    ]);

    return $document;
}
