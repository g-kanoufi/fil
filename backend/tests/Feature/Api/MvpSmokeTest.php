<?php

declare(strict_types=1);
use App\Mail\DripStepMail;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Database\Seeders\WidgetFormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
    $this->seed(WidgetFormSeeder::class);
});
test('admin can login and receives navigation', function () {
    $admin = User::factory()->create([
        'email' => 'admin@fil.test',
        'password' => Hash::make('password'),
        'first_name' => 'FIL',
        'last_name' => 'Admin',
    ]);
    $admin->assignRole('admin');

    $this->postJson('/api/v1/session', [
        'email' => 'admin@fil.test',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('data.email', 'admin@fil.test')
        ->assertJsonPath('data.primary_role', 'admin')
        ->assertJsonStructure([
            'data' => ['navigation', 'permissions', 'roles', 'ui_restrictions'],
        ]);
});
test('prospect cannot access staff app', function () {
    User::factory()->create([
        'email' => 'prospect@fil.test',
        'password' => Hash::make('password'),
    ])->assignRole('prospect');

    $this->postJson('/api/v1/session', [
        'email' => 'prospect@fil.test',
        'password' => 'password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
test('public widget intake creates phase one lead', function () {
    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_dev',
        'email' => 'widget-smoke@fil.test',
        'first_name' => 'Widget',
        'last_name' => 'Smoke',
        'phone' => '+15551234567',
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Widget Smoke Application');

    $lead = Lead::query()->where('title', 'Widget Smoke Application')->first();
    expect($lead)->not->toBeNull();
    expect($lead->pipeline_phase)->toBe(1);
});
test('authenticated staff can hit core mvp endpoints', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@fil.test')->firstOrFail();

    $this->actingAs($admin)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonStructure(['data' => ['leads', 'stores', 'fdd_deliveries', 'pipeline']]);

    $this->actingAs($admin)
        ->getJson('/api/v1/app-config')
        ->assertOk();

    $this->actingAs($admin)
        ->postJson('/api/v1/query/leads', ['limit' => 5])
        ->assertOk();

    $this->actingAs($admin)
        ->getJson('/api/v1/fdds')
        ->assertOk();

    $this->actingAs($admin)
        ->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.email', 'admin@fil.test');
});
test('staff can send communication via mvp composer flow', function () {
    Mail::fake();

    $staff = User::factory()->create();
    $staff->assignRole('franchisor');

    $prospect = User::factory()->create([
        'email' => 'mvp-composer@fil.test',
        'name' => 'Composer Prospect',
    ]);

    $lead = Lead::factory()->create([
        'title' => 'Composer Smoke Lead',
        'prospect_user_id' => $prospect->id,
    ]);

    $this->actingAs($staff)
        ->postJson('/api/v1/communications', [
            'lead_id' => $lead->id,
            'channel' => 'email',
            'subject' => 'MVP smoke',
            'message' => 'Composer path is healthy.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'sent');

    Mail::assertSent(DripStepMail::class);
});
