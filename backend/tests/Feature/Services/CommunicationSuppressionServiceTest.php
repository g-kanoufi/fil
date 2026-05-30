<?php

declare(strict_types=1);
use App\Models\CommunicationSuppression;
use App\Services\Communications\CommunicationSuppressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('suppresses normalized email', function () {
    $service = app(CommunicationSuppressionService::class);

    $service->suppress('email', 'User@Example.COM', 'bounce', 'test');

    expect($service->isSuppressed('email', 'user@example.com'))->toBeTrue();
    expect(CommunicationSuppression::query()->count())->toBe(1);
});
