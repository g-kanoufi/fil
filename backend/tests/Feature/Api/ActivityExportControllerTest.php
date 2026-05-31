<?php

declare(strict_types=1);
use App\Models\ActivityEvent;
use App\Models\Area;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('staff can export activity feed as csv', function () {
    $user = User::factory()->create(['name' => 'Jane Admin']);
    $user->assignRole('admin');

    app(ActivityRecorder::class)->record(
        category: 'auth',
        action: 'login',
        summary: 'Jane Admin signed in',
        actor: $user,
        subject: $user,
    );

    $response = $this->actingAs($user)
        ->get('/api/v1/activity/export?days=30')
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('attachment');

    $body = $response->streamedContent();

    expect($body)->toContain('occurred_at,category,action,summary,actor,subject_type,subject_label,source');
    expect($body)->toContain('Jane Admin signed in');
});

test('staff can export activity feed as json', function () {
    $user = User::factory()->create(['name' => 'JSON Admin']);
    $user->assignRole('admin');

    app(ActivityRecorder::class)->record(
        category: 'auth',
        action: 'login',
        summary: 'JSON Admin signed in',
        actor: $user,
        subject: $user,
    );

    $this->actingAs($user)
        ->getJson('/api/v1/activity/export?days=30&format=json')
        ->assertOk()
        ->assertJsonPath('data.0.summary', 'JSON Admin signed in')
        ->assertJsonStructure([
            'data' => [
                ['id', 'occurred_at', 'actor', 'category', 'action', 'summary', 'source'],
            ],
        ]);
});

test('export is scoped to the area rep assigned records', function () {
    $rep = User::factory()->create();
    $rep->assignRole('area_rep');

    $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
    $visibleLead = Lead::factory()->create(['area_id' => $area->id]);
    $hiddenLead = Lead::factory()->create(['area_id' => null]);
    $visibleStore = Store::factory()->create(['area_id' => $area->id]);

    createExportableEvent('Visible lead event', 'lead', $visibleLead->id, $rep);
    createExportableEvent('Hidden lead event', 'lead', $hiddenLead->id, $rep);
    createExportableEvent('Visible store event', 'store', $visibleStore->id, $rep);

    $body = $this->actingAs($rep)
        ->get('/api/v1/activity/export?days=30')
        ->assertOk()
        ->streamedContent();

    expect($body)->toContain('Visible lead event');
    expect($body)->toContain('Visible store event');
    expect($body)->not->toContain('Hidden lead event');
});

test('export respects category and actor filters', function () {
    $user = User::factory()->create(['name' => 'Filter Admin']);
    $user->assignRole('admin');

    app(ActivityRecorder::class)->record(
        category: 'auth',
        action: 'login',
        summary: 'Auth category event',
        actor: $user,
        subject: $user,
    );
    app(ActivityRecorder::class)->record(
        category: 'settings',
        action: 'updated',
        summary: 'Settings category event',
        actor: $user,
        subject: $user,
    );

    $body = $this->actingAs($user)
        ->get('/api/v1/activity/export?days=30&category=auth')
        ->assertOk()
        ->streamedContent();

    expect($body)->toContain('Auth category event');
    expect($body)->not->toContain('Settings category event');
});

test('unauthenticated users cannot export activity feed', function () {
    $this->getJson('/api/v1/activity/export')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'unauthenticated');
});

test('prospect cannot export activity feed', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    $this->actingAs($user)
        ->getJson('/api/v1/activity/export')
        ->assertForbidden()
        ->assertJsonPath('code', 'staff_required');
});

function createExportableEvent(string $summary, string $subjectType, int $subjectId, User $actor): void
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
