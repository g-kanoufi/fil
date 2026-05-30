<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

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
use Tests\TestCase;

final class DocumentScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_area_rep_can_view_and_download_in_scope_store_document(): void
    {
        $rep = User::factory()->create();
        $rep->assignRole('area_rep');

        $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
        $store = Store::factory()->create(['area_id' => $area->id]);
        $document = $this->createLinkedDocument($store, 'docs/in-scope.pdf');

        $this->actingAs($rep)
            ->getJson("/api/v1/documents/{$document->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Scoped license');

        $this->actingAs($rep)
            ->get("/api/v1/documents/{$document->id}/download")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_area_rep_cannot_view_or_download_out_of_scope_store_document(): void
    {
        $rep = User::factory()->create();
        $rep->assignRole('area_rep');

        Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
        $store = Store::factory()->create(['area_id' => null]);
        $document = $this->createLinkedDocument($store, 'docs/out-of-scope.pdf');

        $this->actingAs($rep)
            ->getJson("/api/v1/documents/{$document->id}")
            ->assertForbidden();

        $this->actingAs($rep)
            ->get("/api/v1/documents/{$document->id}/download")
            ->assertForbidden();
    }

    public function test_staff_without_documents_permission_cannot_access_document_apis(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['app.access', 'stores.view']);

        $store = Store::factory()->create();
        $document = $this->createLinkedDocument($store, 'docs/restricted.pdf');

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
    }

    public function test_document_export_status_is_hidden_from_other_users(): void
    {
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
    }

    public function test_document_export_download_is_hidden_from_other_users(): void
    {
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
    }

    private function createLinkedDocument(Store $store, string $storagePath): Document
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
}
