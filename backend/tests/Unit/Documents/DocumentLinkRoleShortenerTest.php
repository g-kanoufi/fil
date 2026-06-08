<?php

declare(strict_types=1);

use App\Services\Documents\DocumentLinkRoleShortener;
use App\Services\Legacy\LegacyAcfFilePatternBuilder;

test('shortens known long lease amendment role', function () {
    $short = (new DocumentLinkRoleShortener)->forStorage('lease_documents_lease_amendment_extension_options');

    expect($short)->toBe('lease_docs_amend_ext_opts');
    expect(strlen($short))->toBeLessThanOrEqual(DocumentLinkRoleShortener::MAX_LENGTH);
});
test('leaves short roles unchanged', function () {
    expect((new DocumentLinkRoleShortener)->forStorage('doctors_license'))->toBe('doctors_license');
    expect((new DocumentLinkRoleShortener)->forStorage('pre-lease_loi_documents'))->toBe('pre-lease_loi_documents');
});
test('all acf document roles map to unique storage roles within 32 chars', function () {
    $builder = new LegacyAcfFilePatternBuilder;
    $shortener = new DocumentLinkRoleShortener;
    $acfGroups = config('fil-documents.acf_field_groups', []);
    $storageRoles = [];

    foreach ($acfGroups as $path) {
        $paths = is_array($path) ? $path : [$path];

        foreach ($paths as $relative) {
            if (! is_string($relative)) {
                continue;
            }

            $resolved = str_starts_with($relative, '/') ? $relative : base_path($relative);

            foreach ($builder->fromJsonFile($resolved) as $pattern) {
                $storageRole = $shortener->forStorage($pattern['role']);

                expect(strlen($storageRole))->toBeLessThanOrEqual(DocumentLinkRoleShortener::MAX_LENGTH);
                expect($storageRoles)->not->toHaveKey($storageRole);
                $storageRoles[$storageRole] = $pattern['role'];
            }
        }
    }
});
