<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_view_contact_detail(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('franchisor');

        $contact = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]);
        $contact->assignRole('lead_owner');

        $this->actingAs($viewer)
            ->getJson("/api/v1/contacts/{$contact->id}")
            ->assertOk()
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.roles.0', 'lead_owner');
    }

    public function test_staff_without_contacts_permission_cannot_view_contact_detail(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['app.access', 'leads.view']);

        $contact = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/contacts/{$contact->id}")
            ->assertForbidden();
    }
}
