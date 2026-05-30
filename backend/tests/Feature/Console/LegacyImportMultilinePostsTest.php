<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LegacyImportMultilinePostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_leads_from_multiline_insert_statements(): void
    {
        $fixture = base_path('tests/fixtures/legacy-posts-multiline.sql');

        $this->artisan('legacy:import', [
            'dump' => $fixture,
            '--prefix' => 'wp_9_',
            '--only' => 'leads',
            '--execute' => true,
        ])->assertSuccessful();

        $this->assertSame(2, Lead::query()->count());
        $this->assertDatabaseHas('leads', ['legacy_post_id' => 101, 'title' => 'Jane Applicant']);
        $this->assertDatabaseHas('leads', ['legacy_post_id' => 102, 'title' => 'Bob Applicant']);
    }
}
