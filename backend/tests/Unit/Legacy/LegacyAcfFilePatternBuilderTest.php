<?php

declare(strict_types=1);
use App\Services\Legacy\LegacyAcfFilePatternBuilder;

test('builds store doctors license pattern', function () {
    $patterns = (new LegacyAcfFilePatternBuilder)->fromJsonFile(
        base_path('resources/legacy-acf/store.json'),
    );

    $doctorsLicense = collect($patterns)->firstWhere('role', 'doctors_license');

    expect($doctorsLicense)->not->toBeNull();
    expect($doctorsLicense['regex'])->toBe('/^doctors_license_(\d+)_file$/');
});
test('builds location loi document pattern', function () {
    $patterns = (new LegacyAcfFilePatternBuilder)->fromJsonFile(
        base_path('resources/legacy-acf/franchise_location.json'),
    );

    $loi = collect($patterns)->firstWhere('role', 'pre-lease_loi_documents');

    expect($loi)->not->toBeNull();
    expect($loi['role'])->toBe('pre-lease_loi_documents');
    expect(preg_match($loi['regex'], 'pre-lease_loi_documents_0_file'))->toBe(1);
});
