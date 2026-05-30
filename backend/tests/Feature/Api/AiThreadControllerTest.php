<?php

namespace Tests\Feature\Api;

use App\Models\AiThread;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiThreadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_create_thread_and_send_message(): void
    {
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
    }

    public function test_user_cannot_view_another_users_thread(): void
    {
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
    }

    public function test_lead_owner_without_ai_permission_cannot_create_thread(): void
    {
        $user = User::factory()->create();
        $user->assignRole('prospect');

        $this->actingAs($user)
            ->postJson('/api/v1/ai/threads', ['title' => 'Denied'])
            ->assertForbidden();
    }
}
