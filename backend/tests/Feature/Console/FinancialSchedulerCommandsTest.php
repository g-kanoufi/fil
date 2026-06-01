<?php

declare(strict_types=1);
use App\Contracts\Ach\DwollaClient;
use App\Models\AchCustomer;
use App\Models\AchFundingSource;
use App\Models\AchTransfer;
use App\Models\Area;
use App\Models\RoyaltyLineItem;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

test('royalties calculate due skips when disabled', function () {
    Config::set('fil-royalties.enable_royalty_calculation_job', false);

    $this->artisan('royalties:calculate-due')
        ->expectsOutputToContain('disabled')
        ->assertSuccessful();

    expect(RoyaltyPeriod::query()->count())->toBe(0);
});
test('royalties calculate due creates period for eligible store', function () {
    Config::set('fil-royalties.enable_royalty_calculation_job', true);

    Store::factory()->create([
        'status' => 'open',
        'royalty_config' => [
            'default_rate' => 0.06,
            'auto_calculate' => true,
            'scheduled_gross' => 10000,
        ],
    ]);

    $this->artisan('royalties:calculate-due')->assertSuccessful();

    expect(RoyaltyPeriod::query()->count())->toBe(1);
    expect(RoyaltyPeriod::query()->value('total_royalties'))->toBe('600.00');
});
test('ach process due transfers creates transfer and marks line item paid', function () {
    Config::set('fil-royalties.enable_ach_royalty_collection', true);

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

    $lineItem = RoyaltyLineItem::query()->create([
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

    $this->artisan('ach:process-due-transfers')->assertSuccessful();

    $lineItem->refresh();
    expect($lineItem->payment_status)->toBe(1);
    expect($lineItem->ach_transfer_id)->not->toBeNull();
    expect(AchTransfer::query()->count())->toBe(1);
});
test('ach batch does not mark line item paid when dwolla transfer fails', function () {
    Config::set('fil-royalties.enable_ach_royalty_collection', true);
    Config::set('services.dwolla.token', 'test-token');
    Config::set('services.dwolla.destination_funding_source_id', 'dest-fs');

    $fakeDwolla = new class implements DwollaClient
    {
        public function isConfigured(): bool
        {
            return true;
        }

        public function environment(): string
        {
            return 'sandbox';
        }

        public function createClientToken(array $payload): string
        {
            return 'token';
        }

        public function getCustomer(string $customerId): array
        {
            return ['status' => 'verified'];
        }

        public function certifyBeneficialOwnership(string $customerId): bool
        {
            return true;
        }

        public function createTransfer(array $payload): array
        {
            return ['id' => 'transfer-failed', 'status' => 'failed'];
        }

        public function createFundingSource(string $customerId, string $processorToken, ?string $name = null): array
        {
            return ['id' => 'fs-1', 'status' => 'active'];
        }
    };

    $this->app->instance(DwollaClient::class, $fakeDwolla);

    $store = Store::factory()->create();
    AchCustomer::query()->create([
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'provider' => 'dwolla',
        'external_customer_id' => 'cust-batch',
        'status' => 'active',
        'profile' => [],
    ]);
    AchFundingSource::query()->create([
        'ach_customer_id' => AchCustomer::query()->value('id'),
        'external_funding_source_id' => 'source-fs',
        'name' => 'Checking',
        'type' => 'bank',
        'status' => 'active',
        'is_default' => true,
    ]);

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

    $lineItem = RoyaltyLineItem::query()->create([
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

    $this->artisan('ach:process-due-transfers')->assertSuccessful();

    $lineItem->refresh();
    $transfer = AchTransfer::query()->first();

    expect($lineItem->payment_status)->toBe(0);
    expect($lineItem->ach_transfer_id)->toBeNull();
    expect($transfer)->not->toBeNull();
    expect($transfer?->provider_status)->toBe('failed');
    expect($transfer?->status)->toBe(0);
});
test('areas calculate royalties runs on configured day', function () {
    Config::set('fil-royalties.area_royalties_calc_day', now()->day);

    $area = Area::factory()->create(['extras' => ['area_royalty_percentage' => 1]]);
    $store = Store::factory()->create(['area_id' => $area->id, 'status' => 'open']);

    $priorMonth = now()->startOfMonth()->subMonth();

    $period = RoyaltyPeriod::query()->create([
        'store_id' => $store->id,
        'frequency' => 'weekly',
        'period_start' => $priorMonth->copy()->startOfMonth(),
        'period_end' => $priorMonth->copy()->endOfMonth(),
        'recorded_at' => $priorMonth->copy(),
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

    $this->artisan('areas:calculate-royalties')->assertSuccessful();

    $this->assertDatabaseHas('area_royalties', [
        'area_id' => $area->id,
        'amount' => '6.00',
    ]);
});
