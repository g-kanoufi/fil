<?php

declare(strict_types=1);

namespace App\Support\Fields;

final class FieldConfigNormalizer
{
    /**
     * @param  array<string, mixed>|null  $config
     * @return array<string, mixed>|null
     */
    public static function normalize(?array $config): ?array
    {
        if ($config === null) {
            return null;
        }

        if (array_key_exists('choices', $config) && is_array($config['choices'])) {
            $config['choices'] = FieldChoiceSet::normalize($config['choices']);
        }

        return $config;
    }
}
