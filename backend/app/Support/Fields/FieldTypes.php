<?php

declare(strict_types=1);

namespace App\Support\Fields;

/**
 * Canonical catalogue of admin-configurable field types and their storage.
 */
final class FieldTypes
{
    public const TEXT = 'text';

    public const TEXTAREA = 'textarea';

    public const NUMBER = 'number';

    public const RANGE = 'range';

    public const SELECT = 'select';

    public const MULTISELECT = 'multiselect';

    public const TRUE_FALSE = 'true_false';

    public const DATE = 'date';

    public const DATE_TIME = 'date_time';

    public const EMAIL = 'email';

    public const URL = 'url';

    public const RELATION_ONE = 'relation_one';

    public const RELATION_MANY = 'relation_many';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::TEXT,
            self::TEXTAREA,
            self::NUMBER,
            self::RANGE,
            self::SELECT,
            self::MULTISELECT,
            self::TRUE_FALSE,
            self::DATE,
            self::DATE_TIME,
            self::EMAIL,
            self::URL,
            self::RELATION_ONE,
            self::RELATION_MANY,
        ];
    }

    /**
     * @return list<string>
     */
    public static function choiceTypes(): array
    {
        return [self::SELECT, self::MULTISELECT];
    }

    /**
     * @return list<string>
     */
    public static function relationTypes(): array
    {
        return [self::RELATION_ONE, self::RELATION_MANY];
    }

    public static function isRelation(string $type): bool
    {
        return in_array($type, self::relationTypes(), true);
    }

    public static function requiresChoices(string $type): bool
    {
        return in_array($type, self::choiceTypes(), true);
    }

    /**
     * Which `field_values` slot stores the scalar value for a given type.
     */
    public static function valueColumn(string $type): string
    {
        return match ($type) {
            self::NUMBER, self::RANGE, self::RELATION_ONE => 'value_number',
            self::TRUE_FALSE => 'value_boolean',
            self::DATE => 'value_date',
            self::DATE_TIME => 'value_datetime',
            self::MULTISELECT => 'value_json',
            default => 'value_text',
        };
    }
}
