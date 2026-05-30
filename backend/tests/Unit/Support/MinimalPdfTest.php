<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\MinimalPdf;
use Tests\TestCase;

final class MinimalPdfTest extends TestCase
{
    public function test_generates_valid_pdf_bytes(): void
    {
        $pdf = MinimalPdf::generate('PrimeIV Area FDD');

        $this->assertTrue(MinimalPdf::isValid($pdf));
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
    }

    public function test_rejects_invalid_pdf_bytes(): void
    {
        $this->assertFalse(MinimalPdf::isValid("%PDF-1.4\nbroken"));
    }
}
