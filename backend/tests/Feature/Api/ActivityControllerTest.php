<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityEvent;
use App\Models\Area;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_can_fetch_global_activity_feed(): void
    {
        $user = User::factory()->create(['name' => 'Jane Admin']);
        $user->assignRole('admin');

        app(ActivityRecorder::class)->record(
            category: 'auth',
            action: 'login',
            summary: 'Jane Admin signed in',
            actor: $user,
            subject: $user,
        );

        $this->actingAs($user)
            ->getJson('/api/v1/activity?days=30&limit=10')
            ->assertOk()
            ->assertJsonPath('data.0.summary', 'Jane Admin signed in')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'occurred_at', 'actor', 'category', 'action', 'summary'],
                ],
                'meta' => ['next_cursor', 'has_more'],
            ]);
    }

    public function test_staff_can_fetch_subject_timeline(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $lead = \App\Models\Lead::factory()->create(['title' => 'Timeline Lead']);

        ActivityEvent::query()->create([
            'occurred_at' => now(),
            'actor_user_id' => $user->id,
            'actor_name' => $user->name,
            'category' => 'lead',
            'action' => 'updated',
            'summary' => 'Updated timeline lead',
            'subject_type' => 'lead',
            'subject_id' => $lead->id,
            'source' => 'app',
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/activity/subjects/lead/{$lead->id}")
            ->assertOk()
            ->assertJsonPath('data.0.subject.type', 'lead')
            ->assertJsonPath('data.0.subject.id', $lead->id);
    }

    public function test_subject_timeline_merges_domain_projections_for_lead(): void
    {
        $user = User::factory()->create(['name' => 'Phase Mover']);
        $user->assignRole('admin');

        $lead = \App\Models\Lead::factory()->create(['title' => 'Merged Lead']);

        \App\Models\LeadPhaseEvent::query()->create([
            'lead_id' => $lead->id,
            'from_phase' => 1,
            'to_phase' => 2,
            'actor_user_id' => $user->id,
            'created_at' => now()->subHour(),
        ]);

        $phaseEvent = \App\Models\LeadPhaseEvent::query()->where('lead_id', $lead->id)->firstOrFail();

        $communication = \App\Models\Communication::query()->create([
            'lead_id' => $lead->id,
            'type' => 'email',
            'direction' => 'outbound',
            'message' => 'Hello',
            'recipient_name' => 'Prospect',
            'sender_name' => $user->name,
            'sent_at' => now()->subMinutes(30),
            'status' => 'sent',
            'provider' => 'log',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/activity/subjects/lead/{$lead->id}?limit=10")
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains("phase_{$phaseEvent->id}", $ids);
        $this->assertContains("comm_{$communication->id}", $ids);
    }

    public function test_unauthenticated_users_cannot_fetch_activity_feed(): void
    {
        $this->getJson('/api/v1/activity')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_prospect_cannot_fetch_activity_feed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('prospect');

        $this->actingAs($user)
            ->getJson('/api/v1/activity')
            ->assertForbidden()
            ->assertJsonPath('code', 'staff_required');
    }

    public function test_area_rep_global_feed_is_scoped_to_assigned_areas(): void
    {
        $rep = User::factory()->create();
        $rep->assignRole('area_rep');

        $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
        $visibleLead = Lead::factory()->create(['area_id' => $area->id]);
        $hiddenLead = Lead::factory()->create(['area_id' => null]);
        $visibleStore = Store::factory()->create(['area_id' => $area->id]);
        $hiddenStore = Store::factory()->create(['area_id' => null]);

        $this->createActivityEvent('Visible lead event', 'lead', $visibleLead->id, $rep);
        $this->createActivityEvent('Hidden lead event', 'lead', $hiddenLead->id, $rep);
        $this->createActivityEvent('Visible store event', 'store', $visibleStore->id, $rep);
        $this->createActivityEvent('Hidden store event', 'store', $hiddenStore->id, $rep);

        $summaries = collect(
            $this->actingAs($rep)
                ->getJson('/api/v1/activity?limit=50&days=30')
                ->assertOk()
                ->json('data'),
        )->pluck('summary')->all();

        $this->assertContains('Visible lead event', $summaries);
        $this->assertContains('Visible store event', $summaries);
        $this->assertNotContains('Hidden lead event', $summaries);
        $this->assertNotContains('Hidden store event', $summaries);
    }

    private function createActivityEvent(string $summary, string $subjectType, int $subjectId, User $actor): void
    {
        ActivityEvent::query()->create([
            'occurred_at' => now(),
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'category' => $subjectType,
            'action' => 'updated',
            'summary' => $summary,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'source' => 'app',
        ]);
    }
}
