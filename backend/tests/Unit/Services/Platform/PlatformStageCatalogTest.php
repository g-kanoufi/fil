<?php

declare(strict_types=1);

use App\Services\Platform\PlatformStageCatalog;

test('platform stage catalog maps pipeline phases to find sell', function () {
    $catalog = app(PlatformStageCatalog::class);

    expect($catalog->stageForPipelinePhase(1))->toBe('find_sell');
    expect($catalog->stageForPipelinePhase(4))->toBe('find_sell');
    expect($catalog->stageForNavigationId('leads'))->toBe('find_sell');
    expect($catalog->stageForNavigationId('royalties'))->toBe('grow_earn');
});

test('platform stages serialize for app config', function () {
    $stages = app(PlatformStageCatalog::class)->forAppConfig();

    expect($stages)->not->toBeEmpty();
    expect($stages[0])->toHaveKeys(['id', 'label', 'description', 'sort', 'navigation_ids']);
});
