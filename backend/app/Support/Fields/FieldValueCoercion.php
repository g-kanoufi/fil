<?php

declare(strict_types=1);

namespace App\Support\Fields;

use App\Models\Field;

/**
 * Normalizes inbound scalar values before persisting to typed field_values slots.
 */
final class FieldValueCoercion
{
    public static function coerce(Field $field, mixed $value): mixed
    {
        return match ($field->type) {
            FieldTypes::NUMBER, FieldTypes::RANGE => self::toNumber($value),
            FieldTypes::TRUE_FALSE => self::toBoolean($value),
            default => $value,
        };
    }

    public static function canCoerce(Field $field, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return match ($field->type) {
            FieldTypes::NUMBER, FieldTypes::RANGE => self::toNumber($value) !== null,
            FieldTypes::TRUE_FALSE => self::toBoolean($value) !== null,
            default => true,
        };
    }

    public static function toNumber(mixed $value): int|float|null
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/^[\$€£]\s*/u', '', $normalized) ?? $normalized;
        $normalized = str_replace([',', ' ', '%'], '', $normalized);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return str_contains($normalized, '.') ? (float) $normalized : (int) $normalized;
    }

    public static function toBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off', '' => false,
            default => null,
        };
    }
}
