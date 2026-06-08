<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Field;
use Illuminate\Support\Collection;

/**
 * Pre-filters legacy postmeta before values land in extras.
 * Delegates key classification to {@see LegacyExtrasKeyResolver}.
 */
final class LegacyPostMetaHygiene
{
    public function __construct(
        private readonly LegacyExtrasKeyResolver $keyResolver,
    ) {}

    /**
     * @param  Collection<string, Field>  $fieldIndex
     */
    public function shouldImport(
        string $metaKey,
        string $entityType,
        Collection $fieldIndex,
        bool $hasDirectTarget,
        ?array $fieldValueTarget,
    ): bool {
        if ($hasDirectTarget || $fieldValueTarget !== null) {
            return true;
        }

        $resolved = $this->keyResolver->resolve($metaKey, $fieldIndex, $entityType);

        if ($resolved?->isDiscard() === true) {
            return false;
        }

        return $resolved !== null;
    }
}
