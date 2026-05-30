<?php

namespace Tests\Feature\Api;

use App\Models\Document;
use App\Models\User;
use App\Support\MinimalPdf;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_franchisor_can_download_document(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        Storage::disk('local')->put('fdd/unit-fdd.pdf', MinimalPdf::generate('Unit FDD'));

        $document = Document::query()->create([
            'title' => 'Unit FDD',
            'storage_disk' => 'local',
            'storage_path' => 'fdd/unit-fdd.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->get("/api/v1/documents/{$document->id}/download");

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $body = $response->streamedContent();
        $this->assertStringContainsString('startxref', $body);
        $this->assertStringContainsString('%%EOF', $body);
    }

    public function test_franchisor_can_download_document_via_web_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        Storage::disk('local')->put('fdd/unit-fdd.pdf', MinimalPdf::generate('Unit FDD'));

        $document = Document::query()->create([
            'title' => 'Unit FDD',
            'storage_disk' => 'local',
            'storage_path' => 'fdd/unit-fdd.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get("/documents/{$document->id}/download")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_repairs_invalid_placeholder_pdf_on_download(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        Storage::disk('local')->put('fdd/broken.pdf', "%PDF-1.4\nbroken placeholder");

        $document = Document::query()->create([
            'title' => 'Broken Placeholder',
            'storage_disk' => 'local',
            'storage_path' => 'fdd/broken.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'active',
            'extras' => ['placeholder' => true],
        ]);

        $response = $this->actingAs($user)
            ->get("/documents/{$document->id}/download");

        $response->assertOk();
        $body = $response->streamedContent();
        $this->assertStringContainsString('%%EOF', $body);

        $stored = (string) Storage::disk('local')->get('fdd/broken.pdf');
        $this->assertStringContainsString('startxref', $stored);
    }
}
