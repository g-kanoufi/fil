<?php

use App\Models\Lead;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('import command executes fixture dump', function () {
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

    expect(Lead::query()->count())->toBe(1);
    expect(Store::query()->count())->toBe(1);
});
