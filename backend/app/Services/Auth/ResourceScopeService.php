<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\AchTransfer;
use App\Models\ActivityEvent;
use App\Models\ActivityNavigation;
use App\Models\Area;
use App\Models\Closing;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\Fdd;
use App\Models\Lead;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Franchise data boundaries — replaces legacy Zorzees filtering/scoped-access helpers.
 *
 * Tiers (config fil.scope.tiers):
 * - unrestricted: admin, franchisor — no row filters
 * - area: area_rep — rows in assigned areas (+ assigned stores)
 * - store: franchisee, storemanager, employee — assigned stores only; no leads
 */
final class ResourceScopeService
{
    public function tier(User $user): string
    {
        if ($this->hasAnyRole($user, $this->tierRoles('unrestricted'))) {
            return 'unrestricted';
        }

        if ($this->hasAnyRole($user, $this->tierRoles('area'))) {
            return 'area';
        }

        if ($this->hasAnyRole($user, $this->tierRoles('store'))) {
            return 'store';
        }

        return 'unrestricted';
    }

    public function isUnrestricted(User $user): bool
    {
        return $this->tier($user) === 'unrestricted';
    }

    public function mayListLeads(User $user): bool
    {
        if (! $user->can('leads.view')) {
            return false;
        }

        return ! $this->hasAnyRole($user, $this->leadsBlockedRoles());
    }

    /**
     * @return list<int>
     */
    public function assignedStoreIds(User $user): array
    {
        return StoreOwner::query()
            ->where('user_id', $user->id)
            ->pluck('store_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public function assignedAreaIds(User $user): array
    {
        $direct = Area::query()
            ->where('extras->rep_user_id', $user->id)
            ->pluck('id');

        $storeAreaIds = Store::query()
            ->whereIn('id', $this->assignedStoreIds($user))
            ->whereNotNull('area_id')
            ->pluck('area_id');

        return $direct->merge($storeAreaIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function canViewLead(User $user, Lead $lead): bool
    {
        if (! $this->mayListLeads($user)) {
            return false;
        }

        if ($this->isUnrestricted($user)) {
            return true;
        }

        if ($this->tier($user) === 'area') {
            return $lead->area_id !== null && in_array((int) $lead->area_id, $this->assignedAreaIds($user), true);
        }

        return false;
    }

    public function canViewStore(User $user, Store $store): bool
    {
        if (! $user->can('stores.view')) {
            return false;
        }

        if ($this->isUnrestricted($user)) {
            return true;
        }

        $storeIds = $this->assignedStoreIds($user);

        if ($storeIds !== [] && in_array((int) $store->id, $storeIds, true)) {
            return true;
        }

        if ($this->tier($user) === 'area' && $store->area_id !== null) {
            return in_array((int) $store->area_id, $this->assignedAreaIds($user), true);
        }

        return false;
    }

    public function canViewContact(User $user, User $contact): bool
    {
        if (! $user->can('contacts.view')) {
            return false;
        }

        if ($this->isUnrestricted($user)) {
            return true;
        }

        if ((int) $contact->id === (int) $user->id) {
            return true;
        }

        $visibleUserIds = $this->visibleContactUserIds($user);

        return in_array((int) $contact->id, $visibleUserIds, true);
    }

    /**
     * @param  Builder<Lead>  $query
     */
    public function applyLeadScope(Builder $query, User $user): void
    {
        if ($this->isUnrestricted($user) || ! $this->mayListLeads($user)) {
            if (! $this->mayListLeads($user)) {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        if ($this->tier($user) === 'area') {
            $areaIds = $this->assignedAreaIds($user);

            if ($areaIds === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn('area_id', $areaIds);
        }
    }

    /**
     * @param  Builder<Store>  $query
     */
    public function applyStoreScope(Builder $query, User $user): void
    {
        if ($this->isUnrestricted($user)) {
            return;
        }

        $storeIds = $this->assignedStoreIds($user);
        $areaIds = $this->assignedAreaIds($user);

        if ($storeIds === [] && $areaIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $scoped) use ($storeIds, $areaIds): void {
            if ($storeIds !== []) {
                $scoped->whereIn('id', $storeIds);
            }

            if ($areaIds !== []) {
                $scoped->orWhereIn('area_id', $areaIds);
            }
        });
    }

    /**
     * @param  Builder<AchTransfer>  $query
     */
    public function applyAchTransferScope(Builder $query, User $user): void
    {
        if ($this->isUnrestricted($user)) {
            return;
        }

        $storeIds = Store::query()
            ->tap(fn (Builder $storeQuery) => $this->applyStoreScope($storeQuery, $user))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($storeIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('store_id', $storeIds);
    }

    /**
     * @param  Builder<Closing>  $query
     */
    public function applyClosingScope(Builder $query, User $user): void
    {
        if ($this->isUnrestricted($user)) {
            return;
        }

        if (! $this->mayListLeads($user)) {
            $storeIds = $this->assignedStoreIds($user);

            if ($storeIds === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn('store_id', $storeIds);

            return;
        }

        $areaIds = $this->assignedAreaIds($user);
        $storeIds = $this->assignedStoreIds($user);

        if ($areaIds === [] && $storeIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $scoped) use ($areaIds, $storeIds): void {
            if ($areaIds !== []) {
                $scoped->whereIn('area_id', $areaIds)
                    ->orWhereHas('lead', fn (Builder $leadQuery) => $leadQuery->whereIn('area_id', $areaIds));
            }

            if ($storeIds !== []) {
                $scoped->orWhereIn('store_id', $storeIds);
            }
        });
    }

    /**
     * @param  Builder<Fdd>  $query
     */
    public function applyFddScope(Builder $query, User $user): void
    {
        if ($this->isUnrestricted($user)) {
            return;
        }

        $areaIds = $this->assignedAreaIds($user);

        $query->where(function (Builder $scoped) use ($areaIds): void {
            $scoped->where('type', 'unit');

            if ($areaIds !== []) {
                $scoped->orWhere(function (Builder $areaScoped) use ($areaIds): void {
                    $areaScoped->where('type', 'area')->whereIn('area_id', $areaIds);
                });
            }
        });
    }

    /**
     * @param  Builder<ActivityEvent>  $query
     */
    public function applyActivityEventScope(Builder $query, User $user): void
    {
        if ($this->isUnrestricted($user)) {
            return;
        }

        $leadIds = $this->scopedLeadIds($user);
        $storeIds = $this->scopedStoreIds($user);
        $contactIds = $this->visibleContactUserIds($user);

        $query->where(function (Builder $scoped) use ($leadIds, $storeIds, $contactIds): void {
            $matched = false;

            if ($leadIds !== []) {
                $scoped->where(function (Builder $inner) use ($leadIds): void {
                    $inner->where('subject_type', 'lead')->whereIn('subject_id', $leadIds);
                });
                $matched = true;
            }

            if ($storeIds !== []) {
                $method = $matched ? 'orWhere' : 'where';
                $scoped->{$method}(function (Builder $inner) use ($storeIds): void {
                    $inner->where('subject_type', 'store')->whereIn('subject_id', $storeIds);
                });
                $matched = true;
            }

            if ($contactIds !== []) {
                $method = $matched ? 'orWhere' : 'where';
                $scoped->{$method}(function (Builder $inner) use ($contactIds): void {
                    $inner->whereIn('subject_type', ['contact', 'user'])
                        ->whereIn('subject_id', $contactIds);
                });
                $matched = true;
            }

            if (! $matched) {
                $scoped->whereRaw('1 = 0');
            }
        });
    }

    /**
     * @param  Builder<ActivityNavigation>  $query
     */
    public function applyActivityNavigationScope(Builder $query, User $user): void
    {
        if ($this->isUnrestricted($user)) {
            return;
        }

        $leadIds = $this->scopedLeadIds($user);
        $storeIds = $this->scopedStoreIds($user);
        $contactIds = $this->visibleContactUserIds($user);

        $query->where(function (Builder $scoped) use ($leadIds, $storeIds, $contactIds): void {
            $matched = false;

            if ($leadIds !== []) {
                $scoped->where(function (Builder $inner) use ($leadIds): void {
                    $inner->where('subject_type', 'lead')->whereIn('subject_id', $leadIds);
                });
                $matched = true;
            }

            if ($storeIds !== []) {
                $method = $matched ? 'orWhere' : 'where';
                $scoped->{$method}(function (Builder $inner) use ($storeIds): void {
                    $inner->where('subject_type', 'store')->whereIn('subject_id', $storeIds);
                });
                $matched = true;
            }

            if ($contactIds !== []) {
                $method = $matched ? 'orWhere' : 'where';
                $scoped->{$method}(function (Builder $inner) use ($contactIds): void {
                    $inner->whereIn('subject_type', ['contact', 'user'])
                        ->whereIn('subject_id', $contactIds);
                });
                $matched = true;
            }

            $method = $matched ? 'orWhere' : 'where';
            $scoped->{$method}(function (Builder $inner) use ($user): void {
                $inner->where('actor_user_id', $user->id)
                    ->whereNull('subject_type');
            });

            if (! $matched) {
                $scoped->orWhere('actor_user_id', $user->id);
            }
        });
    }

    public function canViewDocument(User $user, Document $document): bool
    {
        if (! $user->can('documents.view')) {
            return false;
        }

        if ($this->isUnrestricted($user)) {
            return true;
        }

        $fddId = data_get($document->extras, 'fdd_id');

        if (is_numeric($fddId)) {
            $fdd = Fdd::query()->find((int) $fddId);

            if ($fdd !== null && $this->canViewFdd($user, $fdd)) {
                return true;
            }
        }

        foreach ($document->links as $link) {
            if ($this->canViewDocumentLink($user, $link)) {
                return true;
            }
        }

        return false;
    }

    public function canViewFdd(User $user, Fdd $fdd): bool
    {
        if (! $user->can('fdd.view')) {
            return false;
        }

        if ($this->isUnrestricted($user)) {
            return true;
        }

        if ($fdd->type === 'unit') {
            return true;
        }

        return $fdd->area_id !== null
            && in_array((int) $fdd->area_id, $this->assignedAreaIds($user), true);
    }

    public function canViewClosing(User $user, Closing $closing): bool
    {
        if (! $this->mayListLeads($user) && ! $user->can('stores.view')) {
            return false;
        }

        if ($this->isUnrestricted($user)) {
            return true;
        }

        if ($closing->store_id !== null) {
            $store = $closing->store;

            if ($store !== null && $this->canViewStore($user, $store)) {
                return true;
            }
        }

        if ($closing->lead_id !== null) {
            $lead = $closing->lead;

            if ($lead !== null && $this->canViewLead($user, $lead)) {
                return true;
            }
        }

        return $closing->area_id !== null
            && in_array((int) $closing->area_id, $this->assignedAreaIds($user), true);
    }

    public function canViewRoyaltyPeriod(User $user, RoyaltyPeriod $period): bool
    {
        if (! $user->can('royalties.view')) {
            return false;
        }

        if ($period->store_id === null) {
            return $this->isUnrestricted($user);
        }

        $store = $period->store;

        return $store !== null && $this->canViewStore($user, $store);
    }

    public function canViewAchTransfer(User $user, AchTransfer $transfer): bool
    {
        if (! $user->can('ach.view')) {
            return false;
        }

        if ($transfer->store_id === null) {
            return $this->isUnrestricted($user);
        }

        $store = $transfer->store;

        return $store !== null && $this->canViewStore($user, $store);
    }

    /**
     * @param  Builder<User>  $query
     */
    public function applyContactScope(Builder $query, User $user): void
    {
        if ($this->isUnrestricted($user)) {
            return;
        }

        $visibleIds = $this->visibleContactUserIds($user);

        if ($visibleIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('users.id', $visibleIds);
    }

    private function canViewDocumentLink(User $user, DocumentLink $link): bool
    {
        $linkable = $link->linkable;

        if ($linkable instanceof Lead) {
            return $this->canViewLead($user, $linkable);
        }

        if ($linkable instanceof Store) {
            return $this->canViewStore($user, $linkable);
        }

        return false;
    }

    /**
     * @return list<int>
     */
    private function scopedLeadIds(User $user): array
    {
        if (! $this->mayListLeads($user)) {
            return [];
        }

        return Lead::query()
            ->tap(fn (Builder $query) => $this->applyLeadScope($query, $user))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function scopedStoreIds(User $user): array
    {
        return Store::query()
            ->tap(fn (Builder $query) => $this->applyStoreScope($query, $user))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function visibleContactUserIds(User $user): array
    {
        $storeIds = $this->assignedStoreIds($user);

        if ($this->tier($user) === 'area') {
            $storeIds = array_values(array_unique(array_merge(
                $storeIds,
                Store::query()->whereIn('area_id', $this->assignedAreaIds($user))->pluck('id')->all(),
            )));
        }

        if ($storeIds === []) {
            return [(int) $user->id];
        }

        $ids = StoreOwner::query()
            ->whereIn('store_id', $storeIds)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->push((int) $user->id)
            ->unique()
            ->values()
            ->all();

        return $ids;
    }

    /**
     * @return list<string>
     */
    private function tierRoles(string $tier): array
    {
        /** @var array<string, list<string>> $tiers */
        $tiers = config('fil.scope.tiers', []);

        return $tiers[$tier] ?? [];
    }

    /**
     * @return list<string>
     */
    private function leadsBlockedRoles(): array
    {
        /** @var list<string> $roles */
        $roles = config('fil.scope.leads_blocked_roles', []);

        return $roles;
    }

    /**
     * @param  list<string>  $roles
     */
    private function hasAnyRole(User $user, array $roles): bool
    {
        if ($roles === []) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }
}
