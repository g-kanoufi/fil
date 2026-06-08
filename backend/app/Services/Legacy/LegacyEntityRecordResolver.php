<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Area;
use App\Models\FranchiseLocation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Store;
use App\Models\User;

final class LegacyEntityRecordResolver
{
    public function resolveRecordId(string $entity, int $legacyPostId, ?string $legacyPostType = null): ?int
    {
        if ($legacyPostType === 'franchise_location' && $entity === 'store') {
            $id = FranchiseLocation::query()->where('legacy_post_id', $legacyPostId)->value('id');

            return $id !== null ? (int) $id : null;
        }

        if ($legacyPostType === 'user' && $entity === 'contact') {
            $id = User::query()->where('legacy_user_id', $legacyPostId)->value('id');

            return $id !== null ? (int) $id : null;
        }

        $id = match ($entity) {
            'lead' => Lead::query()->where('legacy_post_id', $legacyPostId)->value('id'),
            'store' => Store::query()->where('legacy_post_id', $legacyPostId)->value('id'),
            'area' => Area::query()->where('legacy_post_id', $legacyPostId)->value('id'),
            'organization' => Organization::query()->where('legacy_post_id', $legacyPostId)->value('id'),
            'contact' => User::query()->where('legacy_user_id', $legacyPostId)->value('id'),
            default => null,
        };

        return $id !== null ? (int) $id : null;
    }
}
