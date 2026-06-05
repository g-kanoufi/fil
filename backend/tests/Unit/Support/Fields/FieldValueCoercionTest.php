<?php

declare(strict_types=1);

use App\Support\Fields\FieldValueCoercion;

it('parses currency-formatted numbers for legacy postmeta', function (): void {
    expect(FieldValueCoercion::toNumber('$150000.00'))->toBe(150000.0)
        ->and(FieldValueCoercion::toNumber('1,234.56'))->toBe(1234.56)
        ->and(FieldValueCoercion::toNumber('42'))->toBe(42)
        ->and(FieldValueCoercion::toNumber('not-a-number'))->toBeNull();
});

it('parses legacy boolean strings', function (): void {
    expect(FieldValueCoercion::toBoolean('1'))->toBeTrue()
        ->and(FieldValueCoercion::toBoolean('0'))->toBeFalse()
        ->and(FieldValueCoercion::toBoolean('yes'))->toBeTrue();
});
