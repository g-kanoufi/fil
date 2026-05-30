<?php

declare(strict_types=1);
use App\Models\ActivityEvent;
use App\Models\Area;
use App\Models\Communication;
use App\Models\Lead;
use App\Models\LeadPhaseEvent;
use App\Models\Store;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('staff can fetch global activity feed', function () {
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
});
test('staff can fetch subject timeline', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $lead = Lead::factory()->create(['title' => 'Timeline Lead']);

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
});
test('subject timeline merges domain projections for lead', function () {
    $user = User::factory()->create(['name' => 'Phase Mover']);
    $user->assignRole('admin');

    $lead = Lead::factory()->create(['title' => 'Merged Lead']);

    LeadPhaseEvent::query()->create([
        'lead_id' => $lead->id,
        'from_phase' => 1,
        'to_phase' => 2,
        'actor_user_id' => $user->id,
        'created_at' => now()->subHour(),
    ]);

    $phaseEvent = LeadPhaseEvent::query()->where('lead_id', $lead->id)->firstOrFail();

    $communication = Communication::query()->create([
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

    expect($ids)->toContain("phase_{$phaseEvent->id}");
    expect($ids)->toContain("comm_{$communication->id}");
});
test('unauthenticated users cannot fetch activity feed', function () {
    $this->getJson('/api/v1/activity')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'unauthenticated');
});
test('prospect cannot fetch activity feed', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    $this->actingAs($user)
        ->getJson('/api/v1/activity')
        ->assertForbidden()
        ->assertJsonPath('code', 'staff_required');
});
test('area rep global feed is scoped to assigned areas', function () {
    $rep = User::factory()->create();
    $rep->assignRole('area_rep');

    $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
    $visibleLead = Lead::factory()->create(['area_id' => $area->id]);
    $hiddenLead = Lead::factory()->create(['area_id' => null]);
    $visibleStore = Store::factory()->create(['area_id' => $area->id]);
    $hiddenStore = Store::factory()->create(['area_id' => null]);

    createActivityEvent('Visible lead event', 'lead', $visibleLead->id, $rep);
    createActivityEvent('Hidden lead event', 'lead', $hiddenLead->id, $rep);
    createActivityEvent('Visible store event', 'store', $visibleStore->id, $rep);
    createActivityEvent('Hidden store event', 'store', $hiddenStore->id, $rep);

    $summaries = collect(
        $this->actingAs($rep)
            ->getJson('/api/v1/activity?limit=50&days=30')
            ->assertOk()
            ->json('data'),
    )->pluck('summary')->all();

    expect($summaries)->toContain('Visible lead event');
    expect($summaries)->toContain('Visible store event');
    expect($summaries)->not->toContain('Hidden lead event');
    expect($summaries)->not->toContain('Hidden store event');
});
function createActivityEvent(string $summary, string $subjectType, int $subjectId, User $actor): void
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
