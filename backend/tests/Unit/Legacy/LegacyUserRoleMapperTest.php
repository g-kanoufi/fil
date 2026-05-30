<?php

declare(strict_types=1);

namespace Tests\Unit\Legacy;

use App\Services\Legacy\LegacyUserRoleMapper;
use App\Services\Legacy\LegacyWpCapabilitiesParser;
use Tests\TestCase;

final class LegacyUserRoleMapperTest extends TestCase
{
    public function test_parses_wp_capabilities(): void
    {
        $roles = LegacyWpCapabilitiesParser::parseRoles('a:1:{s:13:"administrator";b:1;}');

        $this->assertSame(['administrator'], $roles);
    }

    public function test_maps_franchiseadmin_to_franchisor(): void
    {
        $mapper = new LegacyUserRoleMapper();

        $this->assertSame(['franchisor'], $mapper->mapRoles(['franchiseadmin']));
        $this->assertTrue($mapper->isRelevantStaff(['franchiseadmin']));
        $this->assertFalse($mapper->isProspectOnly(['franchiseadmin']));
    }
}
