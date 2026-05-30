<?php

namespace Tests\Feature\Api;

use App\Models\Closing;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_lead_owner_can_list_closings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        Closing::factory()->count(2)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/closings')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
