<?php

declare(strict_types=1);

use App\Models\Store;
use App\Services\Legacy\LegacyMappingGapsService;
use Database\Seeders\LegacyAcfFieldSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LegacyAcfFieldSeeder::class);
});

test('legacy mapping gaps classifies store postmeta buckets', function () {
    $fixture = base_path('tests/fixtures/legacy-mapping-gaps-store.sql');
    $service = app(LegacyMappingGapsService::class);

    $report = $service->analyze($fixture, 'fil_', 'store', 1);

    expect($report['legacy_posts'])->toBe(1)
        ->and($report['post_type'])->toBe('store')
        ->and(collect($report['buckets']['tier1'])->pluck('key'))->toContain('store_status')
        ->and(collect($report['buckets']['field'])->pluck('key'))->toContain('store_number')
        ->and(collect($report['buckets']['document'])->pluck('key'))->toContain('doctors_license_0_file')
        ->and(collect($report['buckets']['discard'])->pluck('key'))->toContain('finished_photos_group_0_lead_photo_for_website')
        ->and(collect($report['buckets']['discard'])->pluck('key'))->toContain('checklist_0_item')
        ->and(collect($report['buckets']['discard'])->pluck('key'))->toContain('square_access_token')
        ->and(collect($report['buckets']['discard'])->pluck('key'))->toContain('history_table')
        ->and($report['buckets']['gap'])->toBe([]);
});

test('legacy mapping gaps accepts post type override', function () {
    $fixture = base_path('tests/fixtures/legacy-mapping-gaps-store.sql');
    $service = app(LegacyMappingGapsService::class);

    $report = $service->analyze($fixture, 'fil_', 'store', 1, 'store');

    expect($report['post_type'])->toBe('store')
        ->and($report['buckets']['gap'])->toBe([]);
});

test('legacy mapping gaps command fails when unmapped keys remain', function () {
    $fixture = base_path('tests/fixtures/legacy-mapping-gaps-store.sql');
    $contents = file_get_contents($fixture);
    $contents .= "\nINSERT INTO `fil_postmeta` VALUES (99, 9001, 'totally_unknown_store_field', 'x');\n";
    $temp = tempnam(sys_get_temp_dir(), 'fil-gap-');
    file_put_contents($temp, $contents);

    $this->artisan('legacy:mapping-gaps', [
        'dump' => $temp,
        '--prefix' => 'fil_',
        '--entity' => 'store',
        '--min' => 1,
    ])
        ->assertFailed();

    unlink($temp);
});

test('legacy parity report samples option renders field checks', function () {
    Store::factory()->create(['store_status' => 'open', 'extras' => ['orphan_key' => 'x']]);

    $this->artisan('legacy:parity-report', ['--samples' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Post-import spot checks');
});
