<?php

declare(strict_types=1);

use App\Models\ClientSetting;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Lead;
use App\Services\Legacy\LegacyClientOptionsImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('client options import maps network options to client settings', function () {
    $fixture = base_path('tests/fixtures/legacy-options.sql');

    $stats = app(LegacyClientOptionsImportService::class)->import($fixture, 'wp_9_', true);

    expect($stats['applied'])->toBeGreaterThan(0);

    $this->assertDatabaseHas('client_settings', [
        'key' => 'brandName',
    ]);

    expect(ClientSetting::query()->where('key', 'brandName')->value('value'))->toBe('PrimeIV Hydration');
    expect(ClientSetting::query()->where('key', 'topBarColor')->value('value'))->toBe('#53387b');
    expect(ClientSetting::query()->where('key', 'linkColor')->value('value'))->toBe('#0f766e');
    expect(ClientSetting::query()->where('key', 'timeZone')->value('value'))->toBe('America/Denver');
    expect(ClientSetting::query()->where('key', 'clientBranding')->value('value'))->toBeTrue();
    expect(ClientSetting::query()->where('key', 'enable_zai')->value('value'))->toBeTrue();
    expect(ClientSetting::query()->where('key', 'logoUrl')->exists())->toBeFalse();
});

test('legacy import command imports options entity', function () {
    $fixture = base_path('tests/fixtures/legacy-options.sql');

    $this->artisan('legacy:import', [
        'dump' => $fixture,
        '--prefix' => 'wp_9_',
        '--only' => 'options',
        '--execute' => true,
    ])->assertSuccessful();

    expect(ClientSetting::query()->where('key', 'brandName')->value('value'))->toBe('PrimeIV Hydration');
});
