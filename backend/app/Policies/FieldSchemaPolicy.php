<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Settings;
use App\Models\FieldGroup;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;

final class FieldSchemaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', Lead::class)
            || $user->can('viewAny', Store::class)
            || app(ContactPolicy::class)->viewAny($user)
            || $user->can('manage', Settings::class);
    }

    public function viewForEntity(User $user, string $entity): bool
    {
        return match ($entity) {
            'lead' => $user->can('viewAny', Lead::class),
            'store' => $user->can('viewAny', Store::class),
            'contact' => app(ContactPolicy::class)->viewAny($user),
            default => $user->can('manage', Settings::class),
        };
    }

    public function view(User $user, FieldGroup $fieldGroup): bool
    {
        return $this->viewForEntity($user, $fieldGroup->entity);
    }

    public function manage(User $user): bool
    {
        return $user->can('fields.manage');
    }
}
