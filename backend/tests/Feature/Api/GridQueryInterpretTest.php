<?php

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('lead owner can interpret natural language search', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    Lead::factory()->create([
        'title' => 'Hot Waiting Lead',
        'lead_temp' => 'hot',
        'lead_fdd_status' => 'In Waiting Period',
        'pipeline_phase' => 8,
        'owner_user_id' => $user->id,
    ]);

    Lead::factory()->create([
        'title' => 'Cold Active Lead',
        'lead_temp' => 'cold',
        'lead_fdd_status' => 'active',
        'owner_user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/query/leads/interpret', [
            'q' => 'hot leads in waiting period',
        ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'summary',
                'confidence',
                'source',
                'query' => ['sort'],
                'navigation',
                'result_count',
                'original_query',
            ],
        ])
        ->assertJsonPath('data.source', 'heuristic')
        ->assertJsonPath('data.result_count', 1);
});

test('interpret requires minimum query length', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->postJson('/api/v1/query/leads/interpret', ['q' => 'a'])
        ->assertUnprocessable();
});
