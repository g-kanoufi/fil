<?php

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('imports fdds and organizations', function () {
    $fixture = base_path('tests/fixtures/legacy-fdd-org.sql');

    $this->artisan('legacy:import', [
        'dump' => $fixture,
        '--prefix' => 'wp_9_',
        '--only' => 'fdds,organizations',
        '--execute' => true,
    ])->assertSuccessful();

    $this->assertDatabaseHas('fdds', [
        'legacy_post_id' => 301,
        'title' => 'PrimeIV Unit FDD',
        'type' => 'unit',
    ]);

    $this->assertDatabaseHas('organizations', [
        'legacy_post_id' => 401,
        'name' => 'Acme Org',
    ]);
});

test('postmeta promotes tier1 lead columns', function () {
    $posts = base_path('tests/fixtures/legacy-posts.sql');
    $meta = base_path('tests/fixtures/legacy-postmeta.sql');

    $this->artisan('legacy:import', [
        'dump' => $posts,
        '--prefix' => 'wp_9_',
        '--only' => 'leads',
        '--execute' => true,
    ])->assertSuccessful();

    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'referral_notes',
        'name' => 'Referral notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $this->artisan('legacy:import', [
        'dump' => $meta,
        '--prefix' => 'wp_9_',
        '--only' => 'postmeta',
        '--execute' => true,
    ])->assertSuccessful();

    $lead = Lead::query()->where('legacy_post_id', 101)->first();
    expect($lead)->not->toBeNull();
    expect($lead->lead_status)->toBe('active');
    expect($lead->lead_temp)->toBe('hot');
    expect($lead->lead_source)->toBe('widget');

    $field = Field::query()->where('key', 'referral_notes')->firstOrFail();

    $this->assertDatabaseHas('field_values', [
        'entity_type' => 'lead',
        'entity_id' => $lead->id,
        'field_id' => $field->id,
        'value_text' => 'Met at expo',
    ]);

    expect($lead->fresh()->extras ?? [])->not->toHaveKey('referral_notes');
});

test('finalize command runs', function () {
    $this->artisan('legacy:finalize')->assertSuccessful();
});
