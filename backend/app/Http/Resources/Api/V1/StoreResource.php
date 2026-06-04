<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Store;
use App\Services\Fields\EntityFieldValueReader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Store */
final class StoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'area_id' => $this->area_id,
            'status' => $this->status,
            'store_status' => $this->store_status,
            'buildout_started_at' => $this->buildout_started_at?->toDateString(),
            'expected_opening_at' => $this->expected_opening_at?->toDateString(),
            'opened_at' => $this->opened_at?->toDateString(),
            'next_inspection_at' => $this->next_inspection_at?->toDateString(),
            'spa_id' => $this->spa_id,
            'pos_provider' => $this->pos_provider,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'custom' => $this->when(
                $request->routeIs('api.v1.stores.show', 'api.v1.stores.update', 'api.v1.stores.store'),
                fn (): array => app(EntityFieldValueReader::class)->forEntity('store', $this->id),
            ),
        ];
    }
}
