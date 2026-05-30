<?php

use App\Models\AchTransfer;
use App\Models\RoyaltyLineItem;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('import command imports royalty periods and line items', function () {
    $posts = base_path('tests/fixtures/legacy-posts.sql');
    $royalties = base_path('tests/fixtures/legacy-royalties.sql');

    $this->artisan('legacy:import', [
        'dump' => $posts,
        '--prefix' => 'wp_9_',
        '--only' => 'stores',
        '--execute' => true,
    ])->assertSuccessful();

    $this->artisan('legacy:import', [
        'dump' => $royalties,
        '--prefix' => 'wp_9_',
        '--only' => 'royalties',
        '--execute' => true,
    ])->assertSuccessful();

    $store = Store::query()->where('legacy_post_id', 202)->first();
    expect($store)->not->toBeNull();

    expect(RoyaltyPeriod::query()->count())->toBe(1);
    expect(RoyaltyLineItem::query()->count())->toBe(1);

    $period = RoyaltyPeriod::query()->first();
    expect($period->store_id)->toBe($store->id);
    expect($period->gross_revenue)->toBe('12500.00');
});

test('import command imports ach transfers', function () {
    $posts = base_path('tests/fixtures/legacy-posts.sql');
    $ach = base_path('tests/fixtures/legacy-ach.sql');

    $this->artisan('legacy:import', [
        'dump' => $posts,
        '--prefix' => 'wp_9_',
        '--only' => 'stores',
        '--execute' => true,
    ])->assertSuccessful();

    $this->artisan('legacy:import', [
        'dump' => $ach,
        '--prefix' => 'wp_9_',
        '--only' => 'ach',
        '--execute' => true,
    ])->assertSuccessful();

    $this->assertDatabaseHas('ach_transfers', [
        'legacy_transfer_id' => 9001,
        'external_transfer_id' => 'transfer-abc',
        'amount' => 750,
    ]);

    expect(AchTransfer::query()->count())->toBe(1);
});
