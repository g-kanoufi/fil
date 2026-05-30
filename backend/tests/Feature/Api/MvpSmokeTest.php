<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Database\Seeders\WidgetFormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * MVP ship-gate smoke coverage (see docs/MVP_DEPLOY.md).
 */
final class MvpSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UiAccessSeeder::class);
        $this->seed(WidgetFormSeeder::class);
    }

    public function test_admin_can_login_and_receives_navigation(): void
    {
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
    }

    public function test_prospect_cannot_access_staff_app(): void
    {
        User::factory()->create([
            'email' => 'prospect@fil.test',
            'password' => Hash::make('password'),
        ])->assignRole('prospect');

        $this->postJson('/api/v1/session', [
            'email' => 'prospect@fil.test',
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_public_widget_intake_creates_phase_one_lead(): void
    {
        $this->postJson('/api/public/v1/leads', [
            'site_key' => 'pk_dev',
            'email' => 'widget-smoke@fil.test',
            'first_name' => 'Widget',
            'last_name' => 'Smoke',
            'phone' => '+15551234567',
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Widget Smoke Application');

        $lead = Lead::query()->where('title', 'Widget Smoke Application')->first();
        $this->assertNotNull($lead);
        $this->assertSame(1, $lead->pipeline_phase);
    }

    public function test_authenticated_staff_can_hit_core_mvp_endpoints(): void
    {
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
    }

    public function test_staff_can_send_communication_via_mvp_composer_flow(): void
    {
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

        Mail::assertSent(\App\Mail\DripStepMail::class);
    }
}
