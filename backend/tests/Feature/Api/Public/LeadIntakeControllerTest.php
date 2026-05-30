<?php

namespace Tests\Feature\Api\Public;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadIntakeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_lead_intake_validates_payload(): void
    {
        $this->postJson('/api/public/v1/leads', ['site_key' => 'pk_dev'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'first_name', 'last_name']);
    }

    public function test_public_lead_intake_rejects_invalid_site_key(): void
    {
        $this->postJson('/api/public/v1/leads', [
            'site_key' => 'invalid',
            'email' => 'prospect@example.com',
            'first_name' => 'Pat',
            'last_name' => 'Lee',
        ])->assertForbidden();
    }

    public function test_public_lead_intake_creates_prospect_and_lead(): void
    {
        $this->postJson('/api/public/v1/leads', [
            'site_key' => 'pk_dev',
            'email' => 'prospect@example.com',
            'first_name' => 'Pat',
            'last_name' => 'Lee',
            'phone' => '+15551234567',
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Pat Lee Application');

        $this->assertDatabaseHas('users', ['email' => 'prospect@example.com']);
        $this->assertSame(1, Lead::query()->count());

        $prospect = User::query()->where('email', 'prospect@example.com')->first();
        $this->assertTrue($prospect->hasRole('prospect'));
    }

    public function test_public_lead_intake_accepts_site_key_from_header(): void
    {
        $this->postJson('/api/public/v1/leads', [
            'email' => 'header-prospect@example.com',
            'first_name' => 'Header',
            'last_name' => 'Prospect',
        ], ['X-FIL-Site-Key' => 'pk_dev'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Header Prospect Application');
    }

    public function test_public_form_config_requires_site_key(): void
    {
        $this->getJson('/api/public/v1/form-config')
            ->assertUnauthorized();
    }

    public function test_public_form_config_returns_schema_with_site_key(): void
    {
        $this->getJson('/api/public/v1/form-config?site_key=pk_dev')
            ->assertOk()
            ->assertJsonPath('data.form_key', 'lead_short');
    }
}
