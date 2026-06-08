<?php

declare(strict_types=1);

namespace App\Support\Fields;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class LegacyPostTypeFieldScope
{
    public static function apply(Builder|Relation $query, ?string $legacyPostType): void
    {
        if ($legacyPostType === null || $legacyPostType === '') {
            return;
        }

        $query->where('legacy_post_type', $legacyPostType);
    }
}
