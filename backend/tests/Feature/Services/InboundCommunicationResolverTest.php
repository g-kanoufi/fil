<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Lead;
use App\Models\User;
use App\Services\Communications\InboundCommunicationResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InboundCommunicationResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_resolves_lead_by_prospect_email(): void
    {
        $prospect = User::factory()->create(['email' => 'prospect@example.com']);
        $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);

        $match = app(InboundCommunicationResolver::class)->resolveByEmail('prospect@example.com');

        $this->assertSame($lead->id, $match->leadId);
        $this->assertSame($prospect->id, $match->contactUserId);
    }

    public function test_resolves_lead_by_prospect_phone_suffix(): void
    {
        $prospect = User::factory()->create(['phone' => '+1 (555) 123-4567']);
        $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);

        $match = app(InboundCommunicationResolver::class)->resolveByPhone('+15551234567');

        $this->assertSame($lead->id, $match->leadId);
        $this->assertSame($prospect->id, $match->contactUserId);
    }

    public function test_extracts_email_from_rfc_address(): void
    {
        $resolver = app(InboundCommunicationResolver::class);

        $this->assertSame('user@example.com', $resolver->extractEmailAddress('User Name <user@example.com>'));
    }
}
