<?php

declare(strict_types=1);

use App\Actions\Leads\UpdateLead;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\FieldSchemaSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(FieldSchemaSeeder::class);
});

test('updating lead status mirrors fdd status and advances pipeline phase from choice meta', function () {
    $staff = User::factory()->create();
    $staff->assignRole('franchisor');

    $lead = Lead::factory()->create([
        'pipeline_phase' => 1,
        'lead_status' => 'new_lead',
        'lead_fdd_status' => 'new_lead',
    ]);

    app(UpdateLead::class)->handle($lead, [
        'lead_status' => '6',
    ]);

    $lead->refresh();

    expect($lead->lead_status)->toBe('6')
        ->and($lead->lead_fdd_status)->toBe('6')
        ->and($lead->pipeline_phase)->toBe(3);
});

test('app config includes lead_application_status payload', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson('/api/v1/app-config')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'lead_application_status' => [
                    'choices',
                    'groups' => ['active', 'won', 'closed'],
                ],
            ],
        ]);
});
