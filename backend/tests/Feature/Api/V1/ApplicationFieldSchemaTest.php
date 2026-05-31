<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\FieldSchemaSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
    $this->seed(FieldSchemaSeeder::class);
});

test('field schema seeds zorzees application status choices', function () {
    $response = $this->actingAs(adminUser())
        ->getJson('/api/v1/field-groups?entity=lead&context=widget')
        ->assertOk();

    $applications = collect($response->json('data'))->firstWhere('key', 'applications');
    $leadStatus = collect($applications['fields'] ?? [])->firstWhere('key', 'lead_status');

    expect($leadStatus)->not->toBeNull()
        ->and(collect($leadStatus['config']['choices'])->firstWhere('value', '1')['label'])->toBe('New Lead')
        ->and(collect($leadStatus['config']['choices'])->firstWhere('value', '14')['label'])->toBe('Award Franchise (Agreement Signed)')
        ->and($leadStatus['config']['system'] ?? false)->toBeTrue();

    $inactive = collect($leadStatus['config']['choices'])->firstWhere('value', '9');
    expect($inactive['meta']['closed'] ?? false)->toBeTrue();

    $keys = collect($applications['fields'] ?? [])->pluck('key');

    expect($keys)->not->toContain('status', 'store_status', 'open', 'closed');
});

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}
