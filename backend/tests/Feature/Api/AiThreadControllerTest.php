<?php

use App\Models\AiThread;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can create thread and send message', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $create = $this->actingAs($user)
        ->postJson('/api/v1/ai/threads', ['title' => 'Lead help'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Lead help');

    $threadId = $create->json('data.id');

    $this->actingAs($user)
        ->postJson("/api/v1/ai/threads/{$threadId}/messages", [
            'content' => 'Summarize pipeline phase 1 leads.',
        ])
        ->assertOk()
        ->assertJsonPath('data.user_message.role', 'user')
        ->assertJsonPath('data.assistant_message.role', 'assistant');

    $this->actingAs($user)
        ->getJson("/api/v1/ai/threads/{$threadId}")
        ->assertOk()
        ->assertJsonCount(2, 'data.messages');
});

test('user cannot view another users thread', function () {
    $owner = User::factory()->create();
    $owner->assignRole('franchisor');

    $other = User::factory()->create();
    $other->assignRole('franchisor');

    $thread = AiThread::query()->create([
        'user_id' => $owner->id,
        'title' => 'Private',
        'status' => 'active',
    ]);

    $this->actingAs($other)
        ->getJson("/api/v1/ai/threads/{$thread->id}")
        ->assertForbidden();
});

test('lead owner without ai permission cannot create thread', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    $this->actingAs($user)
        ->postJson('/api/v1/ai/threads', ['title' => 'Denied'])
        ->assertForbidden();
});
