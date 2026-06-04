<?php

declare(strict_types=1);

use App\Models\Lead;
use Database\Seeders\FieldSchemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('normalize status command dry run reports planned updates', function () {
    $this->seed(FieldSchemaSeeder::class);

    Lead::factory()->create([
        'lead_status' => '1',
        'lead_fdd_status' => 'disclosed',
        'pipeline_phase' => 1,
    ]);

    $this->artisan('leads:normalize-status', ['--dry-run' => true])
        ->assertSuccessful();
});
