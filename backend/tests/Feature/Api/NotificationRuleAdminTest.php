<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\NotificationRule;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NotificationRuleAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_list_notification_rules(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        NotificationRule::query()->create([
            'hash' => 'admin-list',
            'title' => 'Welcome email',
            'trigger_slug' => 'lead.created',
            'enabled' => true,
            'subject' => 'Hello',
            'body_html' => '<p>Hi</p>',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/notifications/rules')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Welcome email');
    }

    public function test_franchisor_cannot_manage_notification_rules(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $rule = NotificationRule::query()->create([
            'hash' => 'forbidden',
            'title' => 'Test',
            'trigger_slug' => 'lead.created',
            'enabled' => false,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/notifications/rules')
            ->assertForbidden();

        $this->actingAs($user)
            ->patchJson("/api/v1/notifications/rules/{$rule->id}", ['enabled' => true])
            ->assertForbidden();
    }

    public function test_admin_can_toggle_notification_rule(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $rule = NotificationRule::query()->create([
            'hash' => 'toggle-me',
            'title' => 'Toggle test',
            'trigger_slug' => 'lead.created',
            'enabled' => false,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/notifications/rules/{$rule->id}", ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.enabled', true);
    }

    public function test_admin_can_edit_notification_rule_content(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $rule = NotificationRule::query()->create([
            'hash' => 'edit-me',
            'title' => 'Old title',
            'trigger_slug' => 'lead.created',
            'enabled' => true,
            'subject' => 'Old subject',
            'body_html' => '<p>Old</p>',
            'recipients' => ['related:prospect'],
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/notifications/rules/{$rule->id}", [
                'title' => 'New title',
                'subject' => 'New subject',
                'body_html' => '<p>New body</p>',
                'recipients' => ['related:lead_owner', 'related:area_rep'],
                'trigger_slug' => 'lead.updated',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'New title')
            ->assertJsonPath('data.subject', 'New subject')
            ->assertJsonPath('data.trigger_slug', 'lead.updated');
    }

    public function test_admin_can_fetch_notification_rule_schema(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/notifications/rules/schema')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'triggers',
                    'condition_fields',
                    'condition_operators',
                    'schedule_fields',
                    'schedule_directions',
                    'recipient_tokens',
                ],
            ]);
    }

    public function test_admin_can_edit_notification_rule_conditionals_and_schedule(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $rule = NotificationRule::query()->create([
            'hash' => 'edit-conditions',
            'title' => 'Scheduled follow-up',
            'trigger_slug' => 'scheduled.leads',
            'enabled' => true,
            'subject' => 'Reminder',
            'body_html' => '<p>Reminder</p>',
            'recipients' => ['related:prospect'],
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/notifications/rules/{$rule->id}", [
                'conditionals' => [
                    'v' => 2,
                    'mode' => 'send_if',
                    'groups' => [
                        [
                            'match' => 'all',
                            'conditions' => [
                                ['field' => 'lead_temp', 'op' => 'eq', 'value' => 'hot'],
                            ],
                        ],
                    ],
                ],
                'schedule' => [
                    'v' => 2,
                    'field' => 'waiting_period_ends_at',
                    'direction' => 'after',
                    'offset_days' => 1,
                    'offset_hours' => 0,
                    'window_days' => 2,
                    'send_once' => true,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.conditionals.mode', 'send_if')
            ->assertJsonPath('data.conditionals.groups.0.conditions.0.field', 'lead_temp')
            ->assertJsonPath('data.schedule.field', 'waiting_period_ends_at')
            ->assertJsonPath('data.schedule.direction', 'after')
            ->assertJsonPath('data.is_scheduled', true);

        $this->assertDatabaseHas('notification_rules', [
            'id' => $rule->id,
            'trigger_slug' => 'scheduled.leads',
        ]);
    }

    public function test_admin_can_clear_notification_rule_schedule(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $rule = NotificationRule::query()->create([
            'hash' => 'clear-schedule',
            'title' => 'Immediate alert',
            'trigger_slug' => 'lead.created',
            'enabled' => true,
            'schedule' => [
                'v' => 2,
                'field' => 'created_at',
                'direction' => 'on',
                'offset_days' => 0,
                'offset_hours' => 0,
                'window_days' => 1,
                'send_once' => true,
            ],
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/notifications/rules/{$rule->id}", [
                'schedule' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.schedule', null);
    }
}
