<?php

declare(strict_types=1);
use App\Support\MinimalPdf;

test('generates valid pdf bytes', function () {
    $pdf = MinimalPdf::generate('PrimeIV Area FDD');

    expect(MinimalPdf::isValid($pdf))->toBeTrue();
    expect($pdf)->toStartWith('%PDF-1.4');
    $this->assertStringContainsString('%%EOF', $pdf);
});
test('rejects invalid pdf bytes', function () {
    expect(MinimalPdf::isValid("%PDF-1.4\nbroken"))->toBeFalse();
});
