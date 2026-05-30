<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\NotificationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NotificationLegacyTriggerMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_trigger_slugs_normalize_to_fil_triggers(): void
    {
        $rule = NotificationRule::query()->create([
            'hash' => 'legacy-map',
            'title' => 'Welcome',
            'trigger_slug' => 'zrz_user_registered',
            'enabled' => true,
        ]);

        $this->assertSame('user.registered', $rule->normalizedTriggerSlug());
    }
}
