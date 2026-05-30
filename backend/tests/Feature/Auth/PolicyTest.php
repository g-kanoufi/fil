<?php

namespace Tests\Feature\Auth;

use App\Domain\Contact;
use App\Domain\Settings;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_lead_owner_can_view_leads_but_not_stores(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Lead::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Store::class));
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Contact::class));
        $this->assertFalse(Gate::forUser($user)->allows('manage', Settings::class));
    }

    public function test_franchisor_can_access_royalties_and_ach(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $this->assertTrue(Gate::forUser($user)->allows('viewAnyRoyalty'));
        $this->assertTrue(Gate::forUser($user)->allows('viewAnyAch'));
    }

    public function test_prospect_cannot_access_staff_app(): void
    {
        $user = User::factory()->create();
        $user->assignRole('prospect');

        $this->assertFalse(Gate::forUser($user)->allows('accessStaffApp'));
    }

    public function test_admin_can_manage_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue(Gate::forUser($user)->allows('manage', Settings::class));
    }
}
