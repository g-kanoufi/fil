<?php

declare(strict_types=1);

namespace Tests\Unit\Legacy;

use App\Services\Legacy\LegacyAcfFilePatternBuilder;
use Tests\TestCase;

final class LegacyAcfFilePatternBuilderTest extends TestCase
{
    public function test_builds_store_doctors_license_pattern(): void
    {
        $patterns = (new LegacyAcfFilePatternBuilder)->fromJsonFile(
            base_path('resources/legacy-acf/store.json'),
        );

        $doctorsLicense = collect($patterns)->firstWhere('role', 'doctors_license');

        $this->assertNotNull($doctorsLicense);
        $this->assertSame('/^doctors_license_(\d+)_file$/', $doctorsLicense['regex']);
    }

    public function test_builds_location_loi_document_pattern(): void
    {
        $patterns = (new LegacyAcfFilePatternBuilder)->fromJsonFile(
            base_path('resources/legacy-acf/franchise_location.json'),
        );

        $loi = collect($patterns)->firstWhere('role', 'pre-lease_loi_documents');

        $this->assertNotNull($loi);
        $this->assertSame('pre-lease_loi_documents', $loi['role']);
        $this->assertSame(1, preg_match($loi['regex'], 'pre-lease_loi_documents_0_file'));
    }
}
