<?php

declare(strict_types=1);

use App\Support\Legacy\LegacyBundledAcfFieldCatalog;

test('bundled acf catalog indexes store fields from units json', function () {
    $catalog = app(LegacyBundledAcfFieldCatalog::class);
    $index = $catalog->fieldIndexForEntity('store');

    expect($index->has('store_number'))->toBeTrue()
        ->and($index->has('signature_packet'))->toBeTrue()
        ->and($index->has('business_license'))->toBeTrue();
});

test('bundled acf catalog indexes lead application fields', function () {
    $catalog = app(LegacyBundledAcfFieldCatalog::class);
    $index = $catalog->fieldIndexForEntity('lead');

    expect($index->has('assets_group'))->toBeTrue()
        ->and($index->has('lead_source'))->toBeTrue();
});
