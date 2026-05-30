<?php

namespace Tests\Feature\Api;

use App\Models\Area;
use App\Models\Fdd;
use App\Models\Lead;
use App\Models\User;
use App\Support\MinimalPdf;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FddControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_franchisor_can_list_fdds(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        Fdd::query()->create([
            'type' => 'unit',
            'title' => 'PrimeIV Unit FDD',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/fdds')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_franchisor_can_send_fdd_to_lead(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $prospect = User::factory()->create();
        $prospect->assignRole('prospect');

        $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);
        $fdd = Fdd::query()->create([
            'type' => 'unit',
            'title' => 'Unit FDD',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/fdds/{$fdd->id}/leads/{$lead->id}/send")
            ->assertCreated()
            ->assertJsonPath('data.status', 'sent')
            ->assertJsonPath('data.lead_id', $lead->id);

        $this->assertDatabaseHas('fdd_deliveries', [
            'fdd_id' => $fdd->id,
            'lead_id' => $lead->id,
            'recipient_user_id' => $prospect->id,
        ]);

        $lead->refresh();
        $this->assertNotNull($lead->disclosed_at);
    }

    public function test_lead_owner_cannot_send_fdd(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        $lead = Lead::factory()->create();
        $fdd = Fdd::query()->create([
            'type' => 'unit',
            'title' => 'Unit FDD',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/fdds/{$fdd->id}/leads/{$lead->id}/send")
            ->assertForbidden();
    }

    public function test_franchisor_can_create_unit_fdd_with_pdf(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $pdf = UploadedFile::fake()->createWithContent(
            'primeiv-unit-fdd.pdf',
            MinimalPdf::generate('PrimeIV Unit FDD'),
        );

        $response = $this->actingAs($user)
            ->post('/api/v1/fdds', [
                'type' => 'unit',
                'title' => 'PrimeIV Unit FDD 2026',
                'pdf' => $pdf,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'PrimeIV Unit FDD 2026')
            ->assertJsonPath('data.type', 'unit')
            ->assertJsonPath('data.status', 'active');

        $fddId = (int) $response->json('data.id');

        $this->assertDatabaseHas('fdds', [
            'id' => $fddId,
            'type' => 'unit',
            'title' => 'PrimeIV Unit FDD 2026',
            'status' => 'active',
        ]);

        $fdd = Fdd::query()->findOrFail($fddId);
        $this->assertNotNull($fdd->document_id);
        $this->assertDatabaseHas('documents', [
            'id' => $fdd->document_id,
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $user->id,
        ]);
    }

    public function test_franchisor_can_create_area_fdd_for_area(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $area = Area::factory()->create();

        $pdf = UploadedFile::fake()->createWithContent('area-fdd.pdf', MinimalPdf::generate('Area FDD'));

        $this->actingAs($user)
            ->post('/api/v1/fdds', [
                'type' => 'area',
                'title' => 'Denver Area FDD',
                'area_id' => $area->id,
                'pdf' => $pdf,
            ])
            ->assertCreated()
            ->assertJsonPath('data.area_id', $area->id)
            ->assertJsonPath('data.type', 'area');
    }

    public function test_area_fdd_requires_area_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $pdf = UploadedFile::fake()->createWithContent('area-fdd.pdf', MinimalPdf::generate('Area FDD'));

        $this->actingAs($user)
            ->post('/api/v1/fdds', [
                'type' => 'area',
                'title' => 'Missing Area FDD',
                'pdf' => $pdf,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['area_id']);
    }

    public function test_franchisor_can_update_fdd_metadata_and_replace_pdf(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $originalPdf = UploadedFile::fake()->createWithContent('original.pdf', MinimalPdf::generate('Original'));

        $createResponse = $this->actingAs($user)
            ->post('/api/v1/fdds', [
                'type' => 'unit',
                'title' => 'Seed FDD',
                'pdf' => $originalPdf,
            ])
            ->assertCreated();

        $fddId = (int) $createResponse->json('data.id');
        $fdd = Fdd::query()->findOrFail($fddId);
        $originalDocumentId = $fdd->document_id;

        $replacementPdf = UploadedFile::fake()->createWithContent('replacement.pdf', MinimalPdf::generate('Replacement'));

        $this->actingAs($user)
            ->patch("/api/v1/fdds/{$fdd->id}", [
                'title' => 'Updated FDD Title',
                'status' => 'inactive',
                'pdf' => $replacementPdf,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated FDD Title')
            ->assertJsonPath('data.status', 'inactive');

        $fdd->refresh();
        $this->assertSame('Updated FDD Title', $fdd->title);
        $this->assertSame('inactive', $fdd->status);
        $this->assertSame($originalDocumentId, $fdd->document_id);
    }

    public function test_lead_owner_cannot_create_fdd(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        $pdf = UploadedFile::fake()->createWithContent('blocked.pdf', MinimalPdf::generate('Blocked'));

        $this->actingAs($user)
            ->post('/api/v1/fdds', [
                'type' => 'unit',
                'title' => 'Blocked FDD',
                'pdf' => $pdf,
            ])
            ->assertForbidden();
    }
}
