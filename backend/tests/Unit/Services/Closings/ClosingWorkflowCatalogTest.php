<?php

use App\Services\Closings\ClosingWorkflowCatalog;

it('allows configured status transitions', function () {
    $catalog = app(ClosingWorkflowCatalog::class);

    expect($catalog->canTransition('pending', 'scheduled'))->toBeTrue();
    expect($catalog->canTransition('pending', 'completed'))->toBeFalse();
    expect($catalog->label('scheduled'))->toBe('Scheduled');
});

it('normalizes fee lines', function () {
    $catalog = app(ClosingWorkflowCatalog::class);

    $lines = $catalog->normalizeFeeLines([
        ['label' => ' Franchise fee ', 'amount_cents' => 10000],
        ['label' => '', 'amount_cents' => 500],
        ['label' => 'Ignored', 'amount_cents' => 'n/a'],
    ]);

    expect($lines)->toBe([
        ['label' => 'Franchise fee', 'amount_cents' => 10000],
    ]);
});
