<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\AchTransfer;
use App\Models\Area;
use App\Models\RoyaltyLineItem;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class FinancialSchedulerCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_royalties_calculate_due_skips_when_disabled(): void
    {
        Config::set('fil-royalties.enable_royalty_calculation_job', false);

        $this->artisan('royalties:calculate-due')
            ->expectsOutputToContain('disabled')
            ->assertSuccessful();

        $this->assertSame(0, RoyaltyPeriod::query()->count());
    }

    public function test_royalties_calculate_due_creates_period_for_eligible_store(): void
    {
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

        $this->assertSame(1, RoyaltyPeriod::query()->count());
        $this->assertSame('600.00', RoyaltyPeriod::query()->value('total_royalties'));
    }

    public function test_ach_process_due_transfers_creates_transfer_and_marks_line_item_paid(): void
    {
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
        $this->assertSame(1, $lineItem->payment_status);
        $this->assertNotNull($lineItem->ach_transfer_id);
        $this->assertSame(1, AchTransfer::query()->count());
    }

    public function test_areas_calculate_royalties_runs_on_configured_day(): void
    {
        Config::set('fil-royalties.area_royalties_calc_day', now()->day);

        $area = Area::factory()->create(['extras' => ['area_royalty_percentage' => 1]]);
        $store = Store::factory()->create(['area_id' => $area->id, 'status' => 'open']);

        $period = RoyaltyPeriod::query()->create([
            'store_id' => $store->id,
            'frequency' => 'weekly',
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'recorded_at' => now()->subMonth(),
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
    }
}
