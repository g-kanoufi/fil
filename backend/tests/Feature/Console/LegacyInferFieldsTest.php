<?php

declare(strict_types=1);

use App\Services\Legacy\LegacyInferFieldsService;
use App\Services\Legacy\LegacyPostMetaHygiene;
use Database\Seeders\LegacyAcfFieldSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LegacyAcfFieldSeeder::class);
});

test('meta hygiene treats serialized empties as empty', function () {
    $hygiene = app(LegacyPostMetaHygiene::class);

    expect($hygiene->isEmptyValue('a:0:{}'))->toBeTrue()
        ->and($hygiene->isEmptyValue('N;'))->toBeTrue()
        ->and($hygiene->isEmptyValue('active'))->toBeFalse();
});

test('infer fields reports orphan keys in fixture', function () {
    $fixture = base_path('tests/fixtures/legacy-mapping-gaps-store.sql');
    $contents = file_get_contents($fixture);
    $contents .= "\nINSERT INTO `fil_postmeta` VALUES (99, 9001, 'totally_unknown_store_field', 'x');\n";
    $temp = tempnam(sys_get_temp_dir(), 'fil-infer-');
    file_put_contents($temp, $contents);

    $report = app(LegacyInferFieldsService::class)->analyze($temp, 'fil_', 'store', 1);

    expect(collect($report['orphan_keys'])->pluck('key'))->toContain('totally_unknown_store_field');

    unlink($temp);
});
