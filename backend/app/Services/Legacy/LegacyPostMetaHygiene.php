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
        private readonly LegacyPostTypeIndex $postTypes,
    ) {}

    public function isEligiblePost(int $legacyPostId): bool
    {
        return $this->postTypes->isEligible($legacyPostId);
    }

    public function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return true;
        }

        return in_array($trimmed, ['a:0:{}', 'N;', 'b:0;', '[]', '{}', '0'], true);
    }

    /**
     * @param  Collection<string, Field>  $fieldIndex
     */
    public function shouldImport(
        string $metaKey,
        string $entityType,
        Collection $fieldIndex,
        bool $hasDirectTarget,
        ?array $fieldValueTarget,
        int $legacyPostId = 0,
    ): bool {
        if ($legacyPostId > 0 && ! $this->isEligiblePost($legacyPostId)) {
            return false;
        }
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
