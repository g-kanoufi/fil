<?php

declare(strict_types=1);

namespace App\Services\Fields;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\User;
use App\Services\Auth\FieldAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FieldSchemaService
{
    public function __construct(
        private readonly FieldAccessService $fieldAccess,
    ) {}

    /**
     * @return array{
     *     entity: string,
     *     groups: Collection<int, FieldGroup>,
     *     hidden_field_keys: list<string>,
     *     readonly_field_keys: list<string>
     * }
     */
    public function forEntity(User $user, string $entity, ?string $legacyPostType = null): array
    {
        return $this->forRecord($user, $entity, $legacyPostType);
    }

    /**
     * @return array{
     *     entity: string,
     *     legacy_post_type: ?string,
     *     groups: Collection<int, FieldGroup>,
     *     hidden_field_keys: list<string>,
     *     readonly_field_keys: list<string>
     * }
     */
    public function forRecord(User $user, string $entity, ?string $legacyPostType = null): array
    {
        $access = $this->fieldAccess->forUser($user, $entity);

        $groups = FieldGroup::query()
            ->where('status', 'active')
            ->whereHas('fields', function ($query) use ($entity, $access, $legacyPostType): void {
                $query->where('entity', $entity)
                    ->where('status', 'active')
                    ->whereNotIn('key', $access['hidden_field_keys']);
                $this->applyLegacyPostTypeScope($query, $legacyPostType);
            })
            ->with(['fields' => function ($query) use ($entity, $access, $legacyPostType): void {
                $query->where('entity', $entity)
                    ->where('status', 'active')
                    ->whereNotIn('key', $access['hidden_field_keys']);
                $this->applyLegacyPostTypeScope($query, $legacyPostType);
                $query->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return [
            'entity' => $entity,
            'legacy_post_type' => $legacyPostType,
            'groups' => $groups,
            'hidden_field_keys' => $access['hidden_field_keys'],
            'readonly_field_keys' => $access['readonly_field_keys'],
        ];
    }

    /**
     * @param  Builder<Field>  $query
     */
    private function applyLegacyPostTypeScope($query, ?string $legacyPostType): void
    {
        if ($legacyPostType === null || $legacyPostType === '') {
            return;
        }

        $query->where(function ($scoped) use ($legacyPostType): void {
            $scoped->where('legacy_post_type', '')
                ->orWhere('legacy_post_type', $legacyPostType);
        });
    }
}
