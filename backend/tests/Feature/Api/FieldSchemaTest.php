<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\FieldSchemaSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UiAccessSeeder::class);
        $this->seed(FieldSchemaSeeder::class);
    }

    public function test_lead_owner_can_fetch_lead_field_schema(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        $this->actingAs($user)
            ->getJson('/api/v1/fields?entity=lead')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'entity',
                    'groups' => [
                        ['id', 'key', 'title', 'fields' => [['key', 'name', 'type']]],
                    ],
                    'hidden_field_keys',
                    'readonly_field_keys',
                ],
            ])
            ->assertJsonPath('data.entity', 'lead')
            ->assertJsonMissingPath('data.groups.0.fields.2');
    }

    public function test_session_includes_field_access_keys(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        $this->actingAs($user)
            ->getJson('/api/v1/session')
            ->assertOk()
            ->assertJsonPath('data.hidden_field_keys', ['internal_margin_notes']);
    }

    public function test_prospect_cannot_fetch_field_schema(): void
    {
        $user = User::factory()->create();
        $user->assignRole('prospect');

        $this->actingAs($user)
            ->getJson('/api/v1/fields?entity=lead')
            ->assertForbidden();
    }
}
