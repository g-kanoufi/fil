<?php

declare(strict_types=1);

use App\Models\FieldGroup;
use App\Support\Fields\FieldGroupPostTypeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('resolves legacy post type from field group key', function () {
    $applications = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);
    $units = FieldGroup::query()->create([
        'key' => 'units',
        'title' => 'Units',
        'sort_order' => 2,
        'status' => 'active',
    ]);

    expect(FieldGroupPostTypeResolver::resolve($applications))->toBe('application')
        ->and(FieldGroupPostTypeResolver::resolve($units))->toBe('store')
        ->and(FieldGroupPostTypeResolver::groupKeysForEntity('lead'))->toContain('applications')
        ->and(FieldGroupPostTypeResolver::adminGroupKeysForEntity('lead'))->toEqual(['applications', 'applications-advanced'])
        ->and(FieldGroupPostTypeResolver::adminGroupKeysForEntity('lead'))->not->toContain('private-notes')
        ->and(FieldGroupPostTypeResolver::groupKeysForEntity('store'))->toContain('units')
        ->not->toContain('applications');
});
