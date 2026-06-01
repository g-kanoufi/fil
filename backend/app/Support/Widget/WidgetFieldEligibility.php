<?php

declare(strict_types=1);

namespace App\Support\Widget;

use App\Models\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Whether a field may appear in the embed widget form builder palette.
 *
 * Seeded from legacy ACF {@code fl-react-app-column-default}; admins can override.
 */
final class WidgetFieldEligibility
{
    public static function fromAcfFlag(mixed $value): bool
    {
        return in_array($value, [1, '1', true], true);
    }

    public static function isEligible(Field $field): bool
    {
        $config = $field->config;

        if (! is_array($config)) {
            return false;
        }

        return ($config['widget_eligible'] ?? false) === true;
    }

    /**
     * @param  Builder<Field>|Relation  $query
     */
    public static function applyEligibleScope(Builder|Relation $query): void
    {
        $query->where('config->widget_eligible', true);
    }
}
