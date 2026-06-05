<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
});

test('session includes roles permissions and navigation', function () {
    $user = User::factory()->create([
        'email' => 'staff@fil.test',
        'password' => Hash::make('secret'),
    ]);
    $user->assignRole('lead_owner');

    $response = $this->actingAs($user)->getJson('/api/v1/session');

    $response->assertOk()
        ->assertJsonPath('data.primary_role', 'lead_owner')
        ->assertJsonStructure([
            'data' => [
                'permissions',
                'navigation' => [
                    ['id', 'label', 'path'],
                ],
            ],
        ]);

    $navigation = collect($response->json('data.navigation'))
        ->filter(fn (array $item): bool => isset($item['id']))
        ->pluck('id');

    expect($navigation->contains('leads'))->toBeTrue();
    expect($navigation->contains('notifications'))->toBeTrue();
    expect($navigation->contains('settings'))->toBeFalse();
});

test('franchisor session navigation includes closings', function () {
    $user = User::factory()->create([
        'email' => 'nav-franchisor@fil.test',
        'password' => Hash::make('secret'),
    ]);
    $user->assignRole('franchisor');

    $navigation = collect($this->actingAs($user)->getJson('/api/v1/session')
        ->assertOk()
        ->json('data.navigation'))
        ->filter(fn (array $item): bool => isset($item['id']))
        ->pluck('id');

    expect($navigation->contains('closings'))->toBeTrue();
});

test('prospect cannot log in to staff app', function () {
    User::factory()->create([
        'email' => 'prospect@fil.test',
        'password' => Hash::make('secret'),
    ])->assignRole('prospect');

    $this->postJson('/api/v1/session', [
        'email' => 'prospect@fil.test',
        'password' => 'secret',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('unauthenticated session is rejected', function () {
    $this->getJson('/api/v1/session')->assertUnauthorized();
});

test('staff web spa requires authentication', function () {
    $this->get('/reports/leads')->assertRedirect(route('login'));
});

test('authenticated staff can load spa shell', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('id="root"', false);
});

test('login page is public', function () {
    $this->get('/login')->assertOk();
});

test('legacy /app urls redirect to unprefixed staff paths', function () {
    $this->get('/app/login')->assertRedirect('/login');
    $this->get('/app/reports/leads')->assertRedirect('/reports/leads');
});
