<?php

declare(strict_types=1);

use App\Actions\Demo\EnsureDemoProspectPortal;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('fil ensure-demo-prospect repairs portal access', function () {
    $prospect = User::factory()->create(['email' => EnsureDemoProspectPortal::PROSPECT_EMAIL]);
    $prospect->assignRole('prospect');

    expect(Gate::forUser($prospect)->allows('accessProspectPortal'))->toBeFalse();

    $this->artisan('fil:ensure-demo-prospect')->assertSuccessful();

    $prospect->refresh();
    expect(Gate::forUser($prospect)->allows('accessProspectPortal'))->toBeTrue();

    $lead = Lead::query()->where('title', EnsureDemoProspectPortal::LEAD_TITLE)->firstOrFail();
    expect($lead->prospect_user_id)->toBe($prospect->id);
});
