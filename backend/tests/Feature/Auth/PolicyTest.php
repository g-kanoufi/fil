<?php

use App\Domain\Contact;
use App\Domain\Settings;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('lead owner can view leads but not stores', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    expect(Gate::forUser($user)->allows('viewAny', Lead::class))->toBeTrue();
    expect(Gate::forUser($user)->allows('viewAny', Store::class))->toBeFalse();
    expect(Gate::forUser($user)->allows('viewAny', Contact::class))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage', Settings::class))->toBeFalse();
});

test('franchisor can access royalties and ach', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    expect(Gate::forUser($user)->allows('viewAnyRoyalty'))->toBeTrue();
    expect(Gate::forUser($user)->allows('viewAnyAch'))->toBeTrue();
});

test('prospect cannot access staff app', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    expect(Gate::forUser($user)->allows('accessStaffApp'))->toBeFalse();
});

test('prospect with active application can access prospect portal', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    Lead::factory()->create([
        'prospect_user_id' => $user->id,
        'pipeline_phase' => 1,
    ]);

    expect(Gate::forUser($user)->allows('accessProspectPortal'))->toBeTrue();
});

test('prospect without linked lead cannot access prospect portal', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    expect(Gate::forUser($user)->allows('accessProspectPortal'))->toBeFalse();
});

test('admin can manage settings', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect(Gate::forUser($user)->allows('manage', Settings::class))->toBeTrue();
});
