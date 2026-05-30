<?php

declare(strict_types=1);

namespace App\Services\Fdd;

use App\Models\Area;
use App\Models\Fdd;
use App\Models\Lead;

final class FddAreaAvailabilityService
{
    /**
     * @return array{prospect_fdd: bool, prospect_area_fdd: bool, area_id: int|null, area_name: string|null}
     */
    public function forArea(?int $areaId): array
    {
        $area = $areaId !== null ? Area::query()->find($areaId) : null;

        return [
            'prospect_fdd' => $this->hasFddForArea($areaId, 'unit'),
            'prospect_area_fdd' => $this->hasFddForArea($areaId, 'area'),
            'area_id' => $areaId,
            'area_name' => $area?->name,
        ];
    }

    /**
     * @return array{prospect_fdd: bool, prospect_area_fdd: bool, area_id: int|null, area_name: string|null}
     */
    public function forLead(Lead $lead): array
    {
        return $this->forArea($lead->area_id);
    }

    public function hasFddForArea(?int $areaId, string $type): bool
    {
        if ($areaId === null) {
            return Fdd::query()
                ->where('status', 'active')
                ->where('type', $type)
                ->exists();
        }

        return Fdd::query()
            ->where('status', 'active')
            ->where('type', $type)
            ->where(function ($query) use ($areaId): void {
                $query->where('area_id', $areaId)->orWhereNull('area_id');
            })
            ->exists();
    }
}
