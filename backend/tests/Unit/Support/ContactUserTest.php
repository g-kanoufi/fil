<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Contacts\ContactUser;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('prospect-only users are not contact records', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    expect(ContactUser::isContactRecord($user))->toBeFalse();
});

test('staff and franchise roles are contact records', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    expect(ContactUser::isContactRecord($user))->toBeTrue();
});

test('user with prospect plus staff role is still a contact record', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');
    $user->assignRole('lead_owner');

    expect(ContactUser::isContactRecord($user))->toBeTrue();
});

test('users without roles are not contact records', function () {
    $user = User::factory()->create();

    expect(ContactUser::isContactRecord($user))->toBeFalse();
});
