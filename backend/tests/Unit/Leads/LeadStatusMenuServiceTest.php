<?php

declare(strict_types=1);

use App\Services\Leads\LeadStatusMenuService;
use Database\Seeders\FieldSchemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(FieldSchemaSeeder::class);
});

test('lead application status config exposes catalog choices and category groups', function () {
    $service = app(LeadStatusMenuService::class);
    $config = $service->forAppConfig();

    expect($config)->toHaveKeys(['choices', 'groups'])
        ->and($config['groups'])->toHaveKeys(['active', 'won', 'closed'])
        ->and($config['groups']['active']['submenu_key'])->toBe('leads_active')
        ->and($config['groups']['won']['submenu_key'])->toBe('leads_awarded_deals');

    expect($config['choices'])->not->toBeEmpty()
        ->and($config['groups']['active']['filter_values'])->not->toBeEmpty()
        ->and($config['groups']['won']['filter_values'])->not->toBeEmpty();
});

test('active group filter values include non-closed catalog entries', function () {
    $service = app(LeadStatusMenuService::class);
    $config = $service->forAppConfig();

    $active = $service->filterValuesForGroup('active');

    expect($active)->not->toBeEmpty()
        ->and($config['groups']['won']['filter_values'])->not->toBeEmpty();
});
