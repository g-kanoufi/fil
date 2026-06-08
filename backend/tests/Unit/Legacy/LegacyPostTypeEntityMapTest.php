<?php

declare(strict_types=1);

use App\Support\Legacy\LegacyPostTypeEntityMap;

test('maps legacy post types to FIL entities', function () {
    expect(LegacyPostTypeEntityMap::entityFor('application'))->toBe('lead');
    expect(LegacyPostTypeEntityMap::entityFor('store'))->toBe('store');
    expect(LegacyPostTypeEntityMap::entityFor('franchise_location'))->toBe('store');
    expect(LegacyPostTypeEntityMap::entityFor('user'))->toBe('contact');
});

test('assertEntityMatchesPostType rejects mismatched entity', function () {
    LegacyPostTypeEntityMap::assertEntityMatchesPostType('store', 'franchise_location');

    expect(fn () => LegacyPostTypeEntityMap::assertEntityMatchesPostType('lead', 'store'))
        ->toThrow(InvalidArgumentException::class);
});

test('default post type for entity', function () {
    expect(LegacyPostTypeEntityMap::defaultPostTypeForEntity('store'))->toBe('store');
    expect(LegacyPostTypeEntityMap::defaultPostTypeForEntity('lead'))->toBe('application');
    expect(LegacyPostTypeEntityMap::defaultPostTypeForEntity('unknown'))->toBeNull();
});
