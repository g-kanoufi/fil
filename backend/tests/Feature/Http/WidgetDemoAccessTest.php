<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\WidgetForm;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('embed demo requires staff authentication', function () {
    $this->get('/embed-demo')->assertRedirect('/app/login');
    $this->get('/embed-demo/inline')->assertRedirect('/app/login');
    $this->get('/embed-demo/frame')->assertRedirect('/app/login');
});

test('staff can open embed demo preview routes', function () {
    WidgetForm::query()->create([
        'key' => 'lead_short',
        'name' => 'Short',
        'site_key' => 'pk_dev',
        'status' => 'active',
    ]);

    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->get('/embed-demo')
        ->assertRedirect('/app/settings/widget/demo');

    $this->actingAs($user)
        ->get('/embed-demo/inline')
        ->assertOk()
        ->assertSee('data-site-key="pk_dev"', false);

    $this->actingAs($user)
        ->get('/embed-demo/frame')
        ->assertOk()
        ->assertSee('data-site-key="pk_dev"', false);
});
