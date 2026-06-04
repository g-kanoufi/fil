<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legacy spot check lists sample leads and stores', function () {
    $lead = Lead::factory()->create([
        'lead_status' => '1',
        'lead_stage' => 1,
        'legacy_post_id' => 9001,
    ]);
    Store::factory()->create([
        'store_status' => 'open',
        'legacy_post_id' => 8001,
    ]);

    $this->artisan('legacy:spot-check', ['--leads' => 1, '--stores' => 1])
        ->assertSuccessful()
        ->expectsOutputToContain('Lead samples')
        ->expectsOutputToContain('Store samples')
        ->expectsOutputToContain((string) $lead->lead_status);
});

test('legacy spot check fails when database empty', function () {
    $this->artisan('legacy:spot-check')
        ->assertFailed();
});
