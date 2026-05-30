<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Field;
use App\Models\FieldRoleRule;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves per-role field hide/readonly rules (legacy gg_field_options parity).
 */
final class FieldAccessService
{
    /**
     * @return array{hidden_field_keys: list<string>, readonly_field_keys: list<string>}
     */
    public function forUser(User $user, ?string $entity = null): array
    {
        $roles = $user->getRoleNames()->all();

        if ($roles === []) {
            return ['hidden_field_keys' => [], 'readonly_field_keys' => []];
        }

        $query = FieldRoleRule::query()
            ->whereIn('role', $roles)
            ->whereHas('field', function ($builder) use ($entity): void {
                $builder->where('status', 'active');

                if ($entity !== null) {
                    $builder->where('entity', $entity);
                }
            })
            ->with('field:id,key,entity');

        /** @var Collection<int, FieldRoleRule> $rules */
        $rules = $query->get();

        $byField = $rules->groupBy('field_id');

        $hidden = [];
        $readonly = [];

        foreach ($byField as $fieldRules) {
            /** @var FieldRoleRule $first */
            $first = $fieldRules->first();
            $field = $first->field;

            if ($field === null) {
                continue;
            }

            $permissions = $fieldRules->pluck('permission')->all();

            if ($this->isHidden($permissions)) {
                $hidden[] = $field->key;

                continue;
            }

            if ($this->isReadonly($permissions)) {
                $readonly[] = $field->key;
            }
        }

        sort($hidden);
        sort($readonly);

        return [
            'hidden_field_keys' => array_values(array_unique($hidden)),
            'readonly_field_keys' => array_values(array_unique($readonly)),
        ];
    }

    /**
     * @param  list<string>  $permissions
     */
    private function isHidden(array $permissions): bool
    {
        return in_array('hidden', $permissions, true);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function isReadonly(array $permissions): bool
    {
        return in_array('readonly', $permissions, true)
            && ! in_array('write', $permissions, true);
    }
}
