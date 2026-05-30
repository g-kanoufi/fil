<?php

use App\Models\Area;
use App\Models\Fdd;
use App\Models\Lead;
use App\Models\User;
use App\Support\MinimalPdf;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

test('franchisor can list fdds', function () {
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
});

test('franchisor can send fdd to lead', function () {
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
    expect($lead->disclosed_at)->not->toBeNull();
});

test('lead owner cannot send fdd', function () {
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
});

test('franchisor can create unit fdd with pdf', function () {
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
    expect($fdd->document_id)->not->toBeNull();
    $this->assertDatabaseHas('documents', [
        'id' => $fdd->document_id,
        'mime_type' => 'application/pdf',
        'uploaded_by_user_id' => $user->id,
    ]);
});

test('franchisor can create area fdd for area', function () {
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
});

test('area fdd requires area id', function () {
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
});

test('franchisor can update fdd metadata and replace pdf', function () {
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
    expect($fdd->title)->toBe('Updated FDD Title');
    expect($fdd->status)->toBe('inactive');
    expect($fdd->document_id)->toBe($originalDocumentId);
});

test('lead owner cannot create fdd', function () {
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
});
