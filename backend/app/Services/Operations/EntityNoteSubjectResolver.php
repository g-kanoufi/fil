<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Models\Closing;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use App\Support\Contacts\ContactUser;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class EntityNoteSubjectResolver
{
    /**
     * @return list<string>
     */
    public function supportedTypes(): array
    {
        return ['lead', 'store', 'contact', 'closing'];
    }

    public function resolve(string $type, int $id): Model
    {
        return match ($type) {
            'lead' => Lead::query()->findOrFail($id),
            'store' => Store::query()->findOrFail($id),
            'contact' => $this->resolveContact($id),
            'closing' => Closing::query()->findOrFail($id),
            default => throw new InvalidArgumentException("Unsupported note subject type: {$type}"),
        };
    }

    public function authorizeView(object $viewer, Model $subject): void
    {
        match (true) {
            $subject instanceof Lead => abort_unless($viewer->can('view', $subject), 403),
            $subject instanceof Store => abort_unless($viewer->can('view', $subject), 403),
            $subject instanceof User => abort_unless(
                \Illuminate\Support\Facades\Gate::forUser($viewer)->allows('viewContact', $subject),
                403,
            ),
            $subject instanceof Closing => abort_unless($viewer->can('view', $subject), 403),
            default => abort(403),
        };
    }

    public function entityKey(Model $subject): string
    {
        return match (true) {
            $subject instanceof Lead => 'lead',
            $subject instanceof Store => 'store',
            $subject instanceof User => 'contact',
            $subject instanceof Closing => 'closing',
            default => throw new InvalidArgumentException('Unsupported note subject model'),
        };
    }

    public function subjectId(Model $subject): int
    {
        return (int) $subject->getKey();
    }

    public function activityCategory(string $type): string
    {
        return match ($type) {
            'lead' => 'lead',
            'store' => 'store',
            'contact' => 'contact',
            default => 'contact',
        };
    }

    private function resolveContact(int $id): User
    {
        $contact = User::query()->findOrFail($id);
        abort_unless(ContactUser::isContactRecord($contact), 404);

        return $contact;
    }
}
