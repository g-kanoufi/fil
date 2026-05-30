<?php

declare(strict_types=1);
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});
test('franchisor can export documents as zip', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $store = Store::factory()->create(['name' => 'Demo Store', 'spa_id' => 'SPA-1']);
    $document = Document::query()->create([
        'title' => 'Doctors License',
        'mime_type' => 'application/pdf',
        'storage_disk' => 'local',
        'storage_path' => 'exports/license.pdf',
        'status' => 'active',
    ]);

    Storage::disk('local')->put('exports/license.pdf', '%PDF-1.4 export-test');

    DocumentLink::query()->create([
        'document_id' => $document->id,
        'linkable_type' => Store::class,
        'linkable_id' => $store->id,
        'role' => 'doctors_license',
        'sort_order' => 0,
    ]);

    $start = $this->actingAs($user)
        ->postJson('/api/v1/documents/export/start', [
            'entity' => 'store',
            'doc_type' => 'doctors_license',
            'doc_type_label' => 'Doctors License',
        ])
        ->assertOk()
        ->json('data');

    $exportId = $start['export_id'];
    expect($exportId)->not->toBeEmpty();

    $status = $this->actingAs($user)
        ->getJson('/api/v1/documents/export/status?export_id='.$exportId)
        ->assertOk()
        ->json('data');

    expect($status['status'])->toBe('completed');
    expect($status['zip_url'])->not->toBeEmpty();

    $download = $this->actingAs($user)
        ->get($status['zip_url'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/zip');

    $temp = tempnam(sys_get_temp_dir(), 'fil-export-');
    file_put_contents($temp, $download->getContent());

    $zip = new ZipArchive;
    expect($zip->open($temp) === true)->toBeTrue();

    $names = [];

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $names[] = (string) $zip->getNameIndex($index);
    }

    expect(collect($names)->contains(fn (string $name) => str_ends_with($name, '.csv')))->toBeTrue();
    expect(collect($names)->contains(fn (string $name) => str_starts_with($name, 'files/')))->toBeTrue();
    $zip->close();
    @unlink($temp);
});
