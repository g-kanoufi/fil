<?php

declare(strict_types=1);

use App\Models\AchTransfer;
use App\Models\Area;
use App\Models\AreaRoyalty;
use App\Models\PosConnection;
use App\Models\PosSalesSnapshot;
use App\Models\RoyaltyLineItem;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use App\Models\User;
use App\Services\Pos\PosRevenueSyncService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('pos sync creates sales snapshot and updates store revenue fields', function () {
    $store = Store::factory()->create(['royalty_config' => ['default_rate' => 0.06]]);
    $connection = PosConnection::query()->create([
        'store_id' => $store->id,
        'provider' => 'square',
        'external_location_id' => 'LOC123',
        'status' => 'active',
        'meta' => ['sandbox_gross' => 2500, 'sandbox_order_count' => 40],
    ]);

    $snapshot = app(PosRevenueSyncService::class)->sync($connection);

    expect($snapshot->gross_sales)->toBe('2500.00')
        ->and($snapshot->order_count)->toBe(40)
        ->and(PosSalesSnapshot::query()->count())->toBe(1);

    $store->refresh();
    expect(data_get($store->extras, 'last_pos_gross'))->toEqual(2500)
        ->and(data_get($store->royalty_config, 'scheduled_gross'))->toEqual(2500);
});

test('square pos client aggregates order totals from search API', function () {
    Http::fake([
        'connect.squareupsandbox.com/v2/orders/search' => Http::response([
            'orders' => [
                ['state' => 'COMPLETED', 'total_money' => ['amount' => 125000]],
                ['state' => 'COMPLETED', 'total_money' => ['amount' => 75000]],
                ['state' => 'CANCELED', 'total_money' => ['amount' => 50000]],
            ],
        ], 200),
    ]);

    $store = Store::factory()->create();
    $connection = PosConnection::query()->create([
        'store_id' => $store->id,
        'provider' => 'square',
        'external_location_id' => 'LOC999',
        'status' => 'active',
        'credentials' => ['access_token' => 'test-token'],
    ]);

    $snapshot = app(PosRevenueSyncService::class)->sync($connection);

    expect($snapshot->gross_sales)->toBe('2000.00')
        ->and($snapshot->order_count)->toBe(2);
});

test('franchisor can load ach reconciliation summary', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $period = RoyaltyPeriod::query()->create([
        'store_id' => $store->id,
        'frequency' => 'weekly',
        'period_start' => now()->startOfWeek(),
        'period_end' => now()->endOfWeek(),
        'recorded_at' => now(),
        'gross_revenue' => 5000,
        'total_royalties' => 300,
        'status' => 'calculated',
    ]);

    RoyaltyLineItem::query()->create([
        'royalty_period_id' => $period->id,
        'store_id' => $store->id,
        'frequency' => 'weekly',
        'royalty_type' => 'unit',
        'royalty_name' => 'Unit royalty',
        'gross_revenue' => 5000,
        'royalty_rate' => 0.06,
        'royalty_amount' => 300,
        'payment_status' => 0,
    ]);

    AchTransfer::query()->create([
        'store_id' => $store->id,
        'transferred_at' => now(),
        'provider' => 'dwolla',
        'provider_status' => 'failed',
        'status' => 0,
        'amount' => 300,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/ach-transfers/reconciliation')
        ->assertOk()
        ->assertJsonPath('data.unpaid_line_items.count', 1)
        ->assertJsonPath('data.failed_transfers.count', 1);
});

test('franchisor can load royalty intelligence summary', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $area = Area::factory()->create(['name' => 'West']);
    $store = Store::factory()->create(['area_id' => $area->id, 'name' => 'Scottsdale']);

    $period = RoyaltyPeriod::query()->create([
        'store_id' => $store->id,
        'frequency' => 'weekly',
        'period_start' => now()->startOfWeek(),
        'period_end' => now()->endOfWeek(),
        'recorded_at' => now(),
        'gross_revenue' => 10000,
        'total_royalties' => 600,
        'status' => 'calculated',
    ]);

    RoyaltyLineItem::query()->create([
        'royalty_period_id' => $period->id,
        'store_id' => $store->id,
        'frequency' => 'weekly',
        'royalty_type' => 'unit',
        'royalty_name' => 'Unit royalty',
        'gross_revenue' => 10000,
        'royalty_rate' => 0.06,
        'royalty_amount' => 600,
        'payment_status' => 0,
    ]);

    AreaRoyalty::query()->create([
        'area_id' => $area->id,
        'period' => now()->startOfMonth()->subMonth(),
        'frequency' => 'monthly',
        'recorded_at' => now(),
        'store_ids' => [$store->id],
        'royalty_line_item_ids' => [],
        'sum_total_sales' => 10000,
        'sum_unit_royalties' => 600,
        'sum_area_royalties' => 600,
        'sum_ach_available' => 600,
        'percentage' => 1,
        'amount' => 6,
        'payment_status' => 0,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/royalties/intelligence?days=30')
        ->assertOk()
        ->assertJsonPath('data.unit.by_store.0.store_name', 'Scottsdale')
        ->assertJsonPath('data.area.by_area.0.area_name', 'West');
});

test('staging check warns when royalty calc job is disabled on staging', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->app['env'] = 'staging';
    config([
        'app.env' => 'staging',
        'fil-royalties.enable_royalty_calculation_job' => false,
        'session.secure' => true,
        'session.same_site' => 'lax',
        'fil-security.csp.enabled' => true,
        'fil-security.csp.report_only' => true,
        'fil.embed.site_keys' => ['pk_live_stagingtest1234567890'],
        'fil.embed_allowed_origins' => ['https://client.example.com'],
        'services.dwolla.webhook_secret' => 'test-secret',
    ]);

    $this->artisan('mvp:staging-check')
        ->assertSuccessful()
        ->expectsOutputToContain('FIL_ENABLE_ROYALTY_CALC_JOB=false');
});
