<?php

declare(strict_types=1);
use App\Models\Area;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('imports store area cpt meta onto stores', function () {
    $fixture = base_path('tests/fixtures/legacy-store-area.sql');

    $this->artisan('legacy:import', [
        'dump' => $fixture,
        '--prefix' => 'wp_9_',
        '--only' => 'areas,stores,postmeta',
        '--execute' => true,
    ])->assertSuccessful();

    $area = Area::query()->where('legacy_post_id', 404)->first();
    $store = Store::query()->where('legacy_post_id', 202)->first();

    expect($area)->not->toBeNull();
    expect($area->name)->toBe('North Region');
    expect($store)->not->toBeNull();
    expect($store->area_id)->toBe($area->id);
    expect($store->store_status)->toBe('open');
});
