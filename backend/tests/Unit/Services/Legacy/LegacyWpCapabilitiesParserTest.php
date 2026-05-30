<?php

use App\Services\Legacy\LegacyWpCapabilitiesParser;

test('parses standard serialized capabilities', function () {
    $roles = LegacyWpCapabilitiesParser::parseRoles('a:1:{s:13:"administrator";b:1;}');

    expect($roles)->toBe(['administrator']);
});

test('parses sql escaped capabilities from dump', function () {
    $roles = LegacyWpCapabilitiesParser::parseRoles('a:1:{s:13:\"administrator\";b:1;}');

    expect($roles)->toBe(['administrator']);
});

test('parses multiple roles', function () {
    $roles = LegacyWpCapabilitiesParser::parseRoles(
        'a:2:{s:14:\"franchiseadmin\";b:1;s:8:\"prospect\";b:1;}',
    );

    expect($roles)->toEqualCanonicalizing(['franchiseadmin', 'prospect']);
});
