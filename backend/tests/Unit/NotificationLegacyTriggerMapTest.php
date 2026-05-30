<?php

declare(strict_types=1);
use App\Models\NotificationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legacy trigger slugs normalize to fil triggers', function () {
    $rule = NotificationRule::query()->create([
        'hash' => 'legacy-map',
        'title' => 'Welcome',
        'trigger_slug' => 'zrz_user_registered',
        'enabled' => true,
    ]);

    expect($rule->normalizedTriggerSlug())->toBe('user.registered');
});
