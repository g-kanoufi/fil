<?php

declare(strict_types=1);

namespace App\Services\Fdd;

use App\Models\Fdd;
use App\Models\Lead;

final class FddLeadResolver
{
    public function __construct(
        private readonly FddAreaAvailabilityService $availability,
    ) {}

    public function resolveForLead(Lead $lead, string $type): ?Fdd
    {
        if (! in_array($type, ['unit', 'area'], true)) {
            return null;
        }

        if (! $this->availability->hasFddForArea($lead->area_id, $type)) {
            return null;
        }

        $query = Fdd::query()
            ->where('status', 'active')
            ->where('type', $type);

        if ($lead->area_id !== null) {
            $areaSpecific = (clone $query)
                ->where('area_id', $lead->area_id)
                ->orderByDesc('version')
                ->orderByDesc('id')
                ->first();

            if ($areaSpecific !== null) {
                return $areaSpecific;
            }
        }

        return $query
            ->whereNull('area_id')
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first()
            ?? $query->orderByDesc('version')->orderByDesc('id')->first();
    }
}
