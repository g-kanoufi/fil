<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FieldAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function group(): FieldGroup
    {
        return FieldGroup::query()->create([
            'key' => 'applications',
            'title' => 'Applications',
            'slug' => 'applications',
            'sort_order' => 1,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_a_text_field(): void
    {
        $group = $this->group();

        $this->actingAs($this->admin())
            ->postJson('/api/v1/fields', [
                'field_group_id' => $group->id,
                'entity' => 'lead',
                'key' => 'referral_notes',
                'name' => 'Referral Notes',
                'type' => 'text',
            ])
            ->assertCreated()
            ->assertJsonPath('data.key', 'referral_notes')
            ->assertJsonPath('data.storage', 'field_value');

        $this->assertDatabaseHas('fields', ['key' => 'referral_notes', 'entity' => 'lead']);
    }

    public function test_admin_can_create_a_relational_field(): void
    {
        $group = $this->group();

        $this->actingAs($this->admin())
            ->postJson('/api/v1/fields', [
                'field_group_id' => $group->id,
                'entity' => 'lead',
                'key' => 'preferred_areas',
                'name' => 'Preferred Areas',
                'type' => 'relation_many',
                'config' => ['related_entity' => 'area'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'relation_many')
            ->assertJsonPath('data.config.related_entity', 'area');
    }

    public function test_relational_field_requires_valid_related_entity(): void
    {
        $group = $this->group();

        $this->actingAs($this->admin())
            ->postJson('/api/v1/fields', [
                'field_group_id' => $group->id,
                'entity' => 'lead',
                'key' => 'bad_relation',
                'name' => 'Bad Relation',
                'type' => 'relation_one',
                'config' => ['related_entity' => 'not_a_table'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['config.related_entity']);
    }

    public function test_select_field_requires_choices(): void
    {
        $group = $this->group();

        $this->actingAs($this->admin())
            ->postJson('/api/v1/fields', [
                'field_group_id' => $group->id,
                'entity' => 'lead',
                'key' => 'temperature',
                'name' => 'Temperature',
                'type' => 'select',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['config.choices']);
    }

    public function test_admin_can_reorder_fields(): void
    {
        $group = $this->group();
        $first = Field::query()->create([
            'field_group_id' => $group->id, 'entity' => 'lead', 'key' => 'a', 'name' => 'A',
            'type' => 'text', 'storage' => 'field_value', 'sort_order' => 1, 'status' => 'active',
        ]);
        $second = Field::query()->create([
            'field_group_id' => $group->id, 'entity' => 'lead', 'key' => 'b', 'name' => 'B',
            'type' => 'text', 'storage' => 'field_value', 'sort_order' => 2, 'status' => 'active',
        ]);

        $this->actingAs($this->admin())
            ->postJson('/api/v1/fields/reorder', [
                'items' => [
                    ['id' => $first->id, 'sort_order' => 5],
                    ['id' => $second->id, 'sort_order' => 1],
                ],
            ])
            ->assertOk();

        $this->assertSame(5, $first->refresh()->sort_order);
        $this->assertSame(1, $second->refresh()->sort_order);
    }

    public function test_admin_can_delete_a_field(): void
    {
        $group = $this->group();
        $field = Field::query()->create([
            'field_group_id' => $group->id, 'entity' => 'lead', 'key' => 'gone', 'name' => 'Gone',
            'type' => 'text', 'storage' => 'field_value', 'sort_order' => 1, 'status' => 'active',
        ]);

        $this->actingAs($this->admin())
            ->deleteJson("/api/v1/fields/{$field->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('fields', ['id' => $field->id]);
    }

    public function test_non_manager_cannot_create_fields(): void
    {
        $group = $this->group();
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        $this->actingAs($user)
            ->postJson('/api/v1/fields', [
                'field_group_id' => $group->id,
                'entity' => 'lead',
                'key' => 'nope',
                'name' => 'Nope',
                'type' => 'text',
            ])
            ->assertForbidden();
    }
}
