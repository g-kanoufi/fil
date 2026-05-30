<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

final class ActivitySubjectAuthorizer
{
    /**
     * @throws AuthorizationException
     */
    public function authorize(string $type, int $id): void
    {
        match ($type) {
            'lead' => $this->authorizeLead($id),
            'store' => $this->authorizeStore($id),
            'contact', 'user' => $this->authorizeContact($id),
            default => abort(404, 'Unknown activity subject type.'),
        };
    }

    private function authorizeLead(int $id): void
    {
        $lead = Lead::query()->findOrFail($id);
        Gate::authorize('view', $lead);
    }

    private function authorizeStore(int $id): void
    {
        $store = Store::query()->findOrFail($id);
        Gate::authorize('view', $store);
    }

    private function authorizeContact(int $id): void
    {
        $contact = User::query()->findOrFail($id);
        Gate::authorize('viewContact', $contact);
    }
}
