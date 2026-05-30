<?php

namespace Tests\Unit\Services\Legacy;

use App\Services\Legacy\LegacyWpCapabilitiesParser;
use PHPUnit\Framework\TestCase;

class LegacyWpCapabilitiesParserTest extends TestCase
{
    public function test_parses_standard_serialized_capabilities(): void
    {
        $roles = LegacyWpCapabilitiesParser::parseRoles('a:1:{s:13:"administrator";b:1;}');

        $this->assertSame(['administrator'], $roles);
    }

    public function test_parses_sql_escaped_capabilities_from_dump(): void
    {
        $roles = LegacyWpCapabilitiesParser::parseRoles('a:1:{s:13:\"administrator\";b:1;}');

        $this->assertSame(['administrator'], $roles);
    }

    public function test_parses_multiple_roles(): void
    {
        $roles = LegacyWpCapabilitiesParser::parseRoles(
            'a:2:{s:14:\"franchiseadmin\";b:1;s:8:\"prospect\";b:1;}',
        );

        $this->assertEqualsCanonicalizing(['franchiseadmin', 'prospect'], $roles);
    }
}
