<?php

declare(strict_types=1);
use App\Services\Legacy\LegacyUserRoleMapper;
use App\Services\Legacy\LegacyWpCapabilitiesParser;

test('parses wp capabilities', function () {
    $roles = LegacyWpCapabilitiesParser::parseRoles('a:1:{s:13:"administrator";b:1;}');

    expect($roles)->toBe(['administrator']);
});
test('maps franchiseadmin to franchisor', function () {
    $mapper = new LegacyUserRoleMapper;

    expect($mapper->mapRoles(['franchiseadmin']))->toBe(['franchisor']);
    expect($mapper->isRelevantStaff(['franchiseadmin']))->toBeTrue();
    expect($mapper->isProspectOnly(['franchiseadmin']))->toBeFalse();
});
