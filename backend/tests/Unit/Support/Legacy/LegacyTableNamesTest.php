<?php

declare(strict_types=1);

use App\Support\Legacy\LegacyTableNames;

test('resolves network prefix from multisite table prefix', function () {
    $names = LegacyTableNames::fromSitePrefix('vnzokz0zw_9_');

    expect($names->sitePrefix())->toBe('vnzokz0zw_9_')
        ->and($names->networkPrefix())->toBe('vnzokz0zw_')
        ->and($names->siteTable('postmeta'))->toBe('vnzokz0zw_9_postmeta')
        ->and($names->networkTable('users'))->toBe('vnzokz0zw_users');
});

test('resolves wp multisite prefix like production dumps', function () {
    $names = LegacyTableNames::fromSitePrefix('wp_9_');

    expect($names->networkPrefix())->toBe('wp_')
        ->and($names->networkTable('usermeta'))->toBe('wp_usermeta');
});

test('falls back to site prefix when pattern does not match', function () {
    $names = LegacyTableNames::fromSitePrefix('custom_');

    expect($names->networkPrefix())->toBe('custom_')
        ->and($names->networkTable('usermeta'))->toBe('custom_usermeta');
});
