<?php

declare(strict_types=1);
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('imports leads from multiline insert statements', function () {
    $fixture = base_path('tests/fixtures/legacy-posts-multiline.sql');

    $this->artisan('legacy:import', [
        'dump' => $fixture,
        '--prefix' => 'wp_9_',
        '--only' => 'leads',
        '--execute' => true,
    ])->assertSuccessful();

    expect(Lead::query()->count())->toBe(2);
    $this->assertDatabaseHas('leads', ['legacy_post_id' => 101, 'title' => 'Jane Applicant']);
    $this->assertDatabaseHas('leads', ['legacy_post_id' => 102, 'title' => 'Bob Applicant']);
});
