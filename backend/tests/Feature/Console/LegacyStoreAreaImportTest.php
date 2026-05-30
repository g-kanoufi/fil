<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Area;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LegacyStoreAreaImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_store_area_cpt_meta_onto_stores(): void
    {
        $fixture = base_path('tests/fixtures/legacy-store-area.sql');

        $this->artisan('legacy:import', [
            'dump' => $fixture,
            '--prefix' => 'wp_9_',
            '--only' => 'areas,stores,postmeta',
            '--execute' => true,
        ])->assertSuccessful();

        $area = Area::query()->where('legacy_post_id', 404)->first();
        $store = Store::query()->where('legacy_post_id', 202)->first();

        $this->assertNotNull($area);
        $this->assertSame('North Region', $area->name);
        $this->assertNotNull($store);
        $this->assertSame($area->id, $store->area_id);
        $this->assertSame('open', $store->store_status);
    }
}
