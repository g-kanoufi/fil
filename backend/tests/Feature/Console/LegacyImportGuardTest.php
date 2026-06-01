<?php

declare(strict_types=1);

use App\Models\ActivityEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legacy import refuses staging execute without force', function () {
    app()->detectEnvironment(fn (): string => 'staging');

    $this->artisan('legacy:import', [
        'dump' => base_path('tests/fixtures/legacy-posts.sql'),
        '--prefix' => 'wp_9_',
        '--only' => 'leads',
        '--execute' => true,
    ])->assertFailed();
});

test('legacy import refuses staging force without typed confirm', function () {
    app()->detectEnvironment(fn (): string => 'staging');

    $this->artisan('legacy:import', [
        'dump' => base_path('tests/fixtures/legacy-posts.sql'),
        '--prefix' => 'wp_9_',
        '--only' => 'leads',
        '--execute' => true,
        '--force' => true,
    ])->assertFailed();
});

test('legacy import records audit event when forced in staging', function () {
    app()->detectEnvironment(fn (): string => 'staging');

    $this->artisan('legacy:import', [
        'dump' => base_path('tests/fixtures/legacy-posts.sql'),
        '--prefix' => 'wp_9_',
        '--only' => 'leads',
        '--execute' => true,
        '--force' => true,
        '--confirm' => 'legacy-import',
    ])->assertSuccessful();

    expect(ActivityEvent::query()->where('category', 'import')->where('action', 'imported')->count())->toBe(1);
});
