<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Models\FddDelivery;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;

final class ProspectFddService
{
    /**
     * @return Collection<int, FddDelivery>
     */
    public function deliveriesForProspect(User $prospect): Collection
    {
        $leadIds = Lead::query()
            ->where('prospect_user_id', $prospect->id)
            ->pluck('id');

        if ($leadIds->isEmpty()) {
            return collect();
        }

        return FddDelivery::query()
            ->with(['fdd'])
            ->whereIn('lead_id', $leadIds)
            ->orderByDesc('sent_at')
            ->get();
    }

    public function deliveryForProspect(User $prospect, FddDelivery $delivery): FddDelivery
    {
        abort_unless(
            Lead::query()
                ->where('id', $delivery->lead_id)
                ->where('prospect_user_id', $prospect->id)
                ->exists(),
            404,
        );

        return $delivery->load(['fdd']);
    }
}
