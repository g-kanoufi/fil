<?php

declare(strict_types=1);

namespace App\Actions\Stores;

use App\Models\Store;

final class UpdateStore
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Store $store, array $attributes): Store
    {
        $store->update(collect($attributes)->only([
            'name',
            'area_id',
            'status',
            'store_status',
            'buildout_started_at',
            'expected_opening_at',
            'opened_at',
            'next_inspection_at',
            'spa_id',
            'pos_provider',
            'pos_external_id',
            'royalty_config',
        ])->filter(fn ($value) => $value !== null)->all());

        return $store->fresh();
    }
}
