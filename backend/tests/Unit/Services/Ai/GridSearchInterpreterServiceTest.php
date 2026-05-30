<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\Ai\GridSearchInterpreterService;
use App\Services\Auth\ResourceScopeService;
use App\Services\Leads\LeadPipelineCatalog;

beforeEach(function () {
    $this->interpreter = new GridSearchInterpreterService(
        new LeadPipelineCatalog,
        app(ResourceScopeService::class),
    );
});
test('interprets hot leads in waiting period', function () {
    $user = new User(['id' => 1]);

    $result = $this->interpreter->interpret($user, 'leads', 'hot leads in waiting period');

    expect($result['source'])->toBe('heuristic');
    $this->assertStringContainsString('waiting period', strtolower($result['summary']));
    expect($result['query']['filters'] ?? [])->toHaveKey('lead_temp');
    expect($result['query']['filters'] ?? [])->toHaveKey('pipeline_phase');
    expect($result['navigation']['leadtemp'] ?? null)->toBe('hot');
});
test('interprets awarded deals with sort', function () {
    $user = new User(['id' => 1]);

    $result = $this->interpreter->interpret($user, 'leads', 'newest awarded deals');

    $this->assertStringContainsString('awarded', strtolower($result['summary']));
    expect($result['query']['sort'][0]['field'])->toBe('updated_at');
    expect($result['query']['sort'][0]['direction'])->toBe('desc');
});
test('interprets store status', function () {
    $user = new User(['id' => 1]);

    $result = $this->interpreter->interpret($user, 'stores', 'open stores newest first');

    expect($result['query']['filters'] ?? [])->toHaveKey('store_status');
    expect($result['query']['sort'][0]['field'])->toBe('updated_at');
});
test('empty query returns guidance', function () {
    $user = new User(['id' => 1]);

    $result = $this->interpreter->interpret($user, 'leads', '  ');

    expect($result['source'])->toBe('none');
    expect($result['query'])->toBe([]);
});
