<?php

declare(strict_types=1);

namespace App\Services\Fields;

use App\Models\FieldGroup;
use App\Models\User;
use App\Services\Auth\FieldAccessService;
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
    public function forEntity(User $user, string $entity): array
    {
        $access = $this->fieldAccess->forUser($user, $entity);

        $groups = FieldGroup::query()
            ->where('status', 'active')
            ->whereHas('fields', function ($query) use ($entity, $access): void {
                $query->where('entity', $entity)
                    ->where('status', 'active')
                    ->whereNotIn('key', $access['hidden_field_keys']);
            })
            ->with(['fields' => function ($query) use ($entity, $access): void {
                $query->where('entity', $entity)
                    ->where('status', 'active')
                    ->whereNotIn('key', $access['hidden_field_keys'])
                    ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return [
            'entity' => $entity,
            'groups' => $groups,
            'hidden_field_keys' => $access['hidden_field_keys'],
            'readonly_field_keys' => $access['readonly_field_keys'],
        ];
    }
}
