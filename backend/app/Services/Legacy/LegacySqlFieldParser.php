<?php

declare(strict_types=1);

namespace App\Services\Legacy;

final class LegacySqlFieldParser
{
    /**
     * @return list<string|null>
     */
    public static function parseFields(string $tupleBody): array
    {
        $fields = [];
        $current = '';
        $inQuote = false;
        $length = strlen($tupleBody);

        for ($index = 0; $index < $length; $index++) {
            $character = $tupleBody[$index];

            if ($inQuote) {
                if ($character === "'" && ($index + 1 >= $length || $tupleBody[$index + 1] !== "'")) {
                    $inQuote = false;

                    continue;
                }

                if ($character === "'" && $tupleBody[$index + 1] === "'") {
                    $current .= "'";
                    $index++;

                    continue;
                }

                $current .= $character;

                continue;
            }

            if ($character === "'") {
                $inQuote = true;

                continue;
            }

            if ($character === ',') {
                $fields[] = self::normalizeField($current);
                $current = '';

                continue;
            }

            $current .= $character;
        }

        $fields[] = self::normalizeField($current);

        return $fields;
    }

    /**
     * @return list<string>
     */
    public static function splitTuples(string $valuesSection): array
    {
        $tuples = [];
        $depth = 0;
        $start = null;
        $length = strlen($valuesSection);

        for ($index = 0; $index < $length; $index++) {
            $character = $valuesSection[$index];

            if ($character === '(') {
                if ($depth === 0) {
                    $start = $index + 1;
                }
                $depth++;

                continue;
            }

            if ($character === ')' && $depth > 0) {
                $depth--;

                if ($depth === 0 && $start !== null) {
                    $tuples[] = substr($valuesSection, $start, $index - $start);
                    $start = null;
                }
            }
        }

        return $tuples;
    }

    private static function normalizeField(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '' || strtoupper($trimmed) === 'NULL') {
            return null;
        }

        return $trimmed;
    }
}
