<?php

declare(strict_types=1);

namespace App\Actions\Stores;

use App\Models\Store;
use Illuminate\Support\Str;

final class CreateStore
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Store
    {
        $name = (string) ($attributes['name'] ?? 'New Store');

        return Store::query()->create([
            'name' => $name,
            'slug' => $attributes['slug'] ?? Str::slug($name).'-'.Str::lower(Str::random(4)),
            'area_id' => $attributes['area_id'] ?? null,
            'status' => $attributes['status'] ?? 'active',
            'store_status' => $attributes['store_status'] ?? null,
            'spa_id' => $attributes['spa_id'] ?? null,
            'pos_provider' => $attributes['pos_provider'] ?? null,
            'pos_external_id' => $attributes['pos_external_id'] ?? null,
            'royalty_config' => $attributes['royalty_config'] ?? null,
        ]);
    }
}
