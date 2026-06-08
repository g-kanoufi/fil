<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Lead;
use App\Services\Legacy\LegacyImportBaselineCompareService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('baseline compare passes after fixture import', function () {
    $posts = base_path('tests/fixtures/legacy-posts.sql');
    $meta = base_path('tests/fixtures/legacy-postmeta.sql');
    $golden = base_path('tests/fixtures/legacy-import-baseline-golden.json');

    $this->artisan('legacy:import', [
        'dump' => $posts,
        '--prefix' => 'wp_9_',
        '--only' => 'leads',
        '--execute' => true,
    ])->assertSuccessful();

    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'referral_notes',
        'name' => 'Referral notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $this->artisan('legacy:import', [
        'dump' => $meta,
        '--prefix' => 'wp_9_',
        '--only' => 'postmeta',
        '--execute' => true,
    ])->assertSuccessful();

    $result = app(LegacyImportBaselineCompareService::class)->compare($golden);

    expect($result['ok'])->toBeTrue("Diffs: ".implode('; ', $result['diffs']));
    expect(Lead::query()->where('legacy_post_id', 101)->value('lead_status'))->toBe('active');
});

test('baseline compare command fails on drift', function () {
    Lead::factory()->create([
        'legacy_post_id' => 101,
        'lead_status' => 'closed',
    ]);

    $this->artisan('legacy:import-baseline-compare', [
        '--golden' => base_path('tests/fixtures/legacy-import-baseline-golden.json'),
    ])->assertFailed();
});
