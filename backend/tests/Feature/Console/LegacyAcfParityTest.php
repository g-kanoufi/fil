<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use Database\Seeders\FieldSchemaSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
    $this->seed(FieldSchemaSeeder::class);
});

test('zorzees acf parity imports core CRM field groups', function () {
    $keys = FieldGroup::query()->orderBy('sort_order')->pluck('key')->all();

    expect($keys)->toContain('applications')
        ->and($keys)->toContain('units')
        ->and($keys)->toContain('locations')
        ->and($keys)->toContain('private-notes-application')
        ->and($keys)->toContain('private-notes-store')
        ->and($keys)->toContain('units-client-fields')
        ->and($keys)->toContain('user-client-fields')
        ->and($keys)->toContain('applications-advanced')
        ->and(count($keys))->toBeGreaterThanOrEqual(12);
});

test('applications fields follow bundled acf order after import', function () {
    $group = FieldGroup::query()->where('key', 'applications')->firstOrFail();

    $ordered = Field::query()
        ->where('field_group_id', $group->id)
        ->where('parent_field_id', 0)
        ->where('legacy_post_type', 'application')
        ->where('status', 'active')
        ->orderBy('sort_order')
        ->orderBy('id')
        ->limit(5)
        ->pluck('key')
        ->all();

    expect($ordered[0] ?? null)->toBe('lead_stage')
        ->and($ordered[1] ?? null)->toBe('lead_status')
        ->and($ordered[2] ?? null)->toBe('lead_progress');

    $advanced = FieldGroup::query()->where('key', 'applications-advanced')->firstOrFail();

    expect(Field::query()
        ->where('field_group_id', $advanced->id)
        ->where('parent_field_id', 0)
        ->where('status', 'active')
        ->orderBy('sort_order')
        ->value('key'))->toBe('show_advanced');
});

test('applications group has zorzees lead status and not store location status fields', function () {
    $group = FieldGroup::query()->where('key', 'applications')->firstOrFail();

    expect(Field::query()->where('field_group_id', $group->id)->where('key', 'lead_status')->exists())->toBeTrue()
        ->and(Field::query()->where('field_group_id', $group->id)->where('key', 'lead_source')->exists())->toBeTrue()
        ->and(Field::query()->where('field_group_id', $group->id)->where('entity', 'lead')->where('key', 'status')->where('status', 'active')->exists())->toBeFalse()
        ->and(Field::query()->where('field_group_id', $group->id)->where('key', 'store_status')->exists())->toBeFalse();
});

test('units and locations fields are store entity not lead', function () {
    $units = FieldGroup::query()->where('key', 'units')->firstOrFail();
    $locations = FieldGroup::query()->where('key', 'locations')->firstOrFail();

    expect(Field::query()->where('field_group_id', $units->id)->where('entity', '!=', 'store')->where('status', 'active')->count())->toBe(0)
        ->and(Field::query()->where('field_group_id', $locations->id)->where('entity', 'lead')->where('status', 'active')->count())->toBe(0)
        ->and(Field::query()->where('field_group_id', $units->id)->where('key', 'store_status')->exists())->toBeTrue();
});

test('areas and organizations fields use correct entities', function () {
    expect(Field::query()->where('entity', 'area')->where('status', 'active')->count())->toBeGreaterThan(10)
        ->and(Field::query()->where('entity', 'organization')->where('status', 'active')->count())->toBeGreaterThan(5);
});

test('contact user group imports profile fields', function () {
    $user = FieldGroup::query()->where('key', 'user')->firstOrFail();

    expect(Field::query()->where('field_group_id', $user->id)->where('entity', 'contact')->where('status', 'active')->count())
        ->toBeGreaterThan(20);
});
