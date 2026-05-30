<?php

namespace Tests\Feature\Console;

use App\Models\Lead;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_command_executes_fixture_dump(): void
    {
        $fixture = base_path('tests/fixtures/legacy-posts.sql');

        $this->artisan('legacy:import', [
            'dump' => $fixture,
            '--prefix' => 'wp_9_',
            '--execute' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('leads', [
            'legacy_post_id' => 101,
            'title' => 'Jane Applicant',
        ]);

        $this->assertDatabaseHas('stores', [
            'legacy_post_id' => 202,
            'name' => 'PrimeIV Demo',
        ]);

        $this->assertSame(1, Lead::query()->count());
        $this->assertSame(1, Store::query()->count());
    }
}
