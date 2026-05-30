<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\CommunicationSuppression;
use App\Services\Communications\CommunicationSuppressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CommunicationSuppressionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_suppresses_normalized_email(): void
    {
        $service = app(CommunicationSuppressionService::class);

        $service->suppress('email', 'User@Example.COM', 'bounce', 'test');

        $this->assertTrue($service->isSuppressed('email', 'user@example.com'));
        $this->assertSame(1, CommunicationSuppression::query()->count());
    }
}
