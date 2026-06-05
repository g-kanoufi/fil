<?php

declare(strict_types=1);

use App\Models\Fdd;
use App\Models\FddDelivery;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Lead;
use App\Models\User;
use App\Services\Portal\ProspectPortalTokenService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('prospect can set password with setup token and access application schema', function () {
    $prospect = User::factory()->create(['email' => 'applicant@example.com']);
    $prospect->assignRole('prospect');

    $lead = Lead::factory()->create([
        'prospect_user_id' => $prospect->id,
        'pipeline_phase' => 1,
    ]);

    $token = app(ProspectPortalTokenService::class)->issueSetupToken($prospect);

    expect($token)->not->toBeNull();

    $this->postJson('/api/portal/v1/setup-password', [
        'token' => $token,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertOk()
        ->assertJsonPath('data.email', 'applicant@example.com');

    $this->actingAs($prospect)
        ->getJson('/api/portal/v1/application')
        ->assertOk()
        ->assertJsonPath('data.lead.id', $lead->id);
});

test('prospect can log in after password is set', function () {
    $prospect = User::factory()->create([
        'email' => 'portal@example.com',
        'password' => Hash::make('secret-pass'),
    ]);
    $prospect->assignRole('prospect');

    Lead::factory()->create([
        'prospect_user_id' => $prospect->id,
        'pipeline_phase' => 2,
    ]);

    $this->postJson('/api/portal/v1/session', [
        'email' => 'portal@example.com',
        'password' => 'secret-pass',
    ])->assertOk()
        ->assertJsonPath('data.email', 'portal@example.com');
});

test('staff user cannot log in to prospect portal', function () {
    $staff = User::factory()->create([
        'email' => 'staff@example.com',
        'password' => Hash::make('secret-pass'),
    ]);
    $staff->assignRole('franchisor');

    $this->postJson('/api/portal/v1/session', [
        'email' => 'staff@example.com',
        'password' => 'secret-pass',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('prospect application update advances pipeline when completion field set', function () {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);
    Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'long_form_complete_date',
        'name' => 'Long form complete',
        'type' => 'date',
        'storage' => 'field_values',
        'sort_order' => 1,
        'required' => false,
        'status' => 'active',
    ]);

    $prospect = User::factory()->create(['password' => Hash::make('secret-pass')]);
    $prospect->assignRole('prospect');

    $lead = Lead::factory()->create([
        'prospect_user_id' => $prospect->id,
        'pipeline_phase' => 3,
    ]);

    $this->actingAs($prospect)
        ->patchJson('/api/portal/v1/application', [
            'values' => ['long_form_complete_date' => '2026-06-03'],
        ])
        ->assertOk();

    expect($lead->fresh()?->pipeline_phase)->toBe(4);
});

test('public lead intake returns portal setup token', function () {
    config(['fil-platform.portal.app_url' => 'https://app.example.com']);

    $response = $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_dev',
        'email' => 'new@example.com',
        'first_name' => 'Pat',
        'last_name' => 'Lee',
    ])->assertCreated();

    expect($response->json('data.portal.setup_token'))->not->toBeEmpty();
    expect($response->json('data.portal.setup_path'))->toBe('/portal/setup');
    expect($response->json('data.portal.redirect_url'))->toContain('https://app.example.com/portal/setup?token=');
    expect($response->json('data.portal.redirect_after_intake'))->toBeTrue();
});

test('staging rejects lead intake without recaptcha when configured', function () {
    $this->app['env'] = 'staging';
    config([
        'fil.recaptcha.secret_key' => 'test-secret',
        'fil.recaptcha.site_key' => 'test-site',
        'fil.embed.site_keys' => ['pk_dev'],
        'fil.embed_allowed_origins' => ['https://widgets.example.com'],
    ]);

    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_dev',
        'email' => 'recaptcha@example.com',
        'first_name' => 'Pat',
        'last_name' => 'Lee',
    ], [
        'Origin' => 'https://widgets.example.com',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['g-recaptcha-response']);
});

test('prospect can list fdd deliveries via portal', function () {
    $prospect = User::factory()->create();
    $prospect->assignRole('prospect');

    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);
    $fdd = Fdd::query()->create(['type' => 'unit', 'title' => 'Unit FDD', 'status' => 'active']);
    $delivery = FddDelivery::query()->create([
        'fdd_id' => $fdd->id,
        'lead_id' => $lead->id,
        'sent_at' => now(),
        'status' => 'sent',
        'delivery_method' => 'email',
        'meta' => [],
    ]);

    $this->actingAs($prospect)
        ->getJson('/api/portal/v1/fdd-deliveries')
        ->assertOk()
        ->assertJsonPath('data.0.id', $delivery->id);
});

test('prospect can begin local fdd sign session via portal', function () {
    config(['fil-platform.esign.driver' => 'local']);

    $prospect = User::factory()->create();
    $prospect->assignRole('prospect');

    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);
    $fdd = Fdd::query()->create(['type' => 'unit', 'title' => 'Unit FDD', 'status' => 'active']);
    $delivery = FddDelivery::query()->create([
        'fdd_id' => $fdd->id,
        'lead_id' => $lead->id,
        'sent_at' => now(),
        'status' => 'sent',
        'delivery_method' => 'email',
        'meta' => [],
    ]);

    $this->actingAs($prospect)
        ->postJson("/api/portal/v1/fdd-deliveries/{$delivery->id}/sign-session")
        ->assertOk()
        ->assertJsonPath('data.mode', 'local')
        ->assertJsonPath('data.driver', 'local');
});

test('prospect can sign fdd delivery locally via portal', function () {
    config(['fil-platform.esign.driver' => 'local']);

    $prospect = User::factory()->create();
    $prospect->assignRole('prospect');

    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);
    $fdd = Fdd::query()->create(['type' => 'unit', 'title' => 'Unit FDD', 'status' => 'active']);
    $delivery = FddDelivery::query()->create([
        'fdd_id' => $fdd->id,
        'lead_id' => $lead->id,
        'sent_at' => now(),
        'status' => 'sent',
        'delivery_method' => 'email',
        'meta' => [],
    ]);

    $this->actingAs($prospect)
        ->postJson("/api/portal/v1/fdd-deliveries/{$delivery->id}/sign", [
            'signed_name' => 'Pat Lee',
            'agree' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'signed');

    expect($delivery->fresh()?->status)->toBe('signed');
});

test('sandbox esign driver returns embedded portal signing url', function () {
    config([
        'fil-platform.esign.driver' => 'sandbox',
        'fil-platform.portal.app_url' => 'https://staging.example.com',
    ]);

    $prospect = User::factory()->create();
    $prospect->assignRole('prospect');

    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);
    $fdd = Fdd::query()->create(['type' => 'unit', 'title' => 'Unit FDD', 'status' => 'active']);
    $delivery = FddDelivery::query()->create([
        'fdd_id' => $fdd->id,
        'lead_id' => $lead->id,
        'sent_at' => now(),
        'status' => 'sent',
        'delivery_method' => 'email',
        'meta' => [],
    ]);

    $response = $this->actingAs($prospect)
        ->postJson("/api/portal/v1/fdd-deliveries/{$delivery->id}/sign-session")
        ->assertOk()
        ->assertJsonPath('data.mode', 'embedded');

    expect($response->json('data.signing_url'))->toContain('/portal/fdd/'.$delivery->id);

    $reference = $response->json('data.vendor_reference');

    $this->actingAs($prospect)
        ->postJson("/api/portal/v1/fdd-deliveries/{$delivery->id}/sign", [
            'signed_name' => 'Pat Lee',
            'agree' => true,
            'vendor_reference' => $reference,
        ])
        ->assertCreated();

    expect($delivery->fresh()?->status)->toBe('signed');
});
