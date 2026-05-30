<?php

namespace Tests\Feature\Console;

use App\Models\Fdd;
use App\Models\Lead;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportFullTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_fdds_and_organizations(): void
    {
        $fixture = base_path('tests/fixtures/legacy-fdd-org.sql');

        $this->artisan('legacy:import', [
            'dump' => $fixture,
            '--prefix' => 'wp_9_',
            '--only' => 'fdds,organizations',
            '--execute' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('fdds', [
            'legacy_post_id' => 301,
            'title' => 'PrimeIV Unit FDD',
            'type' => 'unit',
        ]);

        $this->assertDatabaseHas('organizations', [
            'legacy_post_id' => 401,
            'name' => 'Acme Org',
        ]);
    }

    public function test_postmeta_promotes_tier1_lead_columns(): void
    {
        $posts = base_path('tests/fixtures/legacy-posts.sql');
        $meta = base_path('tests/fixtures/legacy-postmeta.sql');

        $this->artisan('legacy:import', [
            'dump' => $posts,
            '--prefix' => 'wp_9_',
            '--only' => 'leads',
            '--execute' => true,
        ])->assertSuccessful();

        $this->artisan('legacy:import', [
            'dump' => $meta,
            '--prefix' => 'wp_9_',
            '--only' => 'postmeta',
            '--execute' => true,
        ])->assertSuccessful();

        $lead = Lead::query()->where('legacy_post_id', 101)->first();
        $this->assertNotNull($lead);
        $this->assertSame('active', $lead->lead_status);
        $this->assertSame('hot', $lead->lead_temp);
        $this->assertSame('widget', $lead->lead_source);
    }

    public function test_finalize_command_runs(): void
    {
        $this->artisan('legacy:finalize')->assertSuccessful();
    }
}
