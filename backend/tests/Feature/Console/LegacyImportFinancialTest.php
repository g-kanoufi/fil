<?php

namespace Tests\Feature\Console;

use App\Models\AchTransfer;
use App\Models\RoyaltyLineItem;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportFinancialTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_command_imports_royalty_periods_and_line_items(): void
    {
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
        $this->assertNotNull($store);

        $this->assertSame(1, RoyaltyPeriod::query()->count());
        $this->assertSame(1, RoyaltyLineItem::query()->count());

        $period = RoyaltyPeriod::query()->first();
        $this->assertSame($store->id, $period->store_id);
        $this->assertSame('12500.00', $period->gross_revenue);
    }

    public function test_import_command_imports_ach_transfers(): void
    {
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

        $this->assertSame(1, AchTransfer::query()->count());
    }
}
