<?php

declare(strict_types=1);

use App\Models\InterestRegion;
use Database\Seeders\InterestRegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(InterestRegionSeeder::class);
});

test('legacy sync interest region terms links seeded states and creates market regions', function () {
    $fixture = base_path('tests/fixtures/legacy-interest-region-terms.sql');

    $this->artisan('legacy:sync-interest-region-terms', [
        'dump' => $fixture,
        '--prefix' => 'fil_',
        '--execute' => true,
    ])->assertSuccessful();

    $us = InterestRegion::query()->where('code', 'US')->whereNull('parent_id')->firstOrFail();
    expect($us->legacy_term_id)->toBe(2);

    $alabama = InterestRegion::query()
        ->where('parent_id', $us->id)
        ->where('code', 'AL')
        ->firstOrFail();
    expect($alabama->legacy_term_id)->toBe(3);

    $market = InterestRegion::query()
        ->where('legacy_term_id', 380)
        ->where('parent_id', $us->id)
        ->firstOrFail();
    expect($market->name)->toBe('California - Southern')
        ->and($market->slug)->toBe('california-southern');
});

test('legacy sync interest region terms dry run does not write rows', function () {
    $fixture = base_path('tests/fixtures/legacy-interest-region-terms.sql');

    $this->artisan('legacy:sync-interest-region-terms', [
        'dump' => $fixture,
        '--prefix' => 'fil_',
    ])->assertSuccessful();

    expect(InterestRegion::query()->whereNotNull('legacy_term_id')->count())->toBe(0)
        ->and(InterestRegion::query()->where('name', 'California - Southern')->exists())->toBeFalse();
});

test('area_of_interest postmeta resolves after legacy term sync', function () {
    $termsFixture = base_path('tests/fixtures/legacy-interest-region-terms.sql');
    $postsFixture = base_path('tests/fixtures/legacy-posts.sql');
    $metaFixture = base_path('tests/fixtures/legacy-postmeta-interest-region.sql');

    $this->artisan('legacy:sync-interest-region-terms', [
        'dump' => $termsFixture,
        '--prefix' => 'fil_',
        '--execute' => true,
    ])->assertSuccessful();

    $this->artisan('legacy:import', [
        'dump' => $postsFixture,
        '--prefix' => 'wp_9_',
        '--only' => 'leads',
        '--execute' => true,
    ])->assertSuccessful();

    $this->artisan('legacy:import', [
        'dump' => $metaFixture,
        '--prefix' => 'wp_9_',
        '--only' => 'postmeta',
        '--execute' => true,
    ])->assertSuccessful();

    $regionId = InterestRegion::query()->where('legacy_term_id', 380)->value('id');

    $this->assertDatabaseHas('leads', [
        'legacy_post_id' => 101,
        'interest_region_id' => $regionId,
    ]);
});

test('legacy import runs interest region term sync before postmeta on full execute', function () {
    $fixture = base_path('tests/fixtures/legacy-interest-region-terms.sql');

    $this->artisan('legacy:import', [
        'dump' => $fixture,
        '--prefix' => 'fil_',
        '--only' => 'postmeta',
        '--execute' => true,
    ])->assertSuccessful();

    expect(InterestRegion::query()->where('legacy_term_id', 2)->exists())->toBeTrue();
});
