<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Ai;

use App\Models\User;
use App\Services\Ai\GridSearchInterpreterService;
use App\Services\Leads\LeadPipelineCatalog;
use Tests\TestCase;

class GridSearchInterpreterServiceTest extends TestCase
{
    private GridSearchInterpreterService $interpreter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interpreter = new GridSearchInterpreterService(
            new LeadPipelineCatalog,
            app(\App\Services\Auth\ResourceScopeService::class),
        );
    }

    public function test_interprets_hot_leads_in_waiting_period(): void
    {
        $user = new User(['id' => 1]);

        $result = $this->interpreter->interpret($user, 'leads', 'hot leads in waiting period');

        $this->assertSame('heuristic', $result['source']);
        $this->assertStringContainsString('waiting period', strtolower($result['summary']));
        $this->assertArrayHasKey('lead_temp', $result['query']['filters'] ?? []);
        $this->assertArrayHasKey('pipeline_phase', $result['query']['filters'] ?? []);
        $this->assertSame('hot', $result['navigation']['leadtemp'] ?? null);
    }

    public function test_interprets_awarded_deals_with_sort(): void
    {
        $user = new User(['id' => 1]);

        $result = $this->interpreter->interpret($user, 'leads', 'newest awarded deals');

        $this->assertStringContainsString('awarded', strtolower($result['summary']));
        $this->assertSame('updated_at', $result['query']['sort'][0]['field']);
        $this->assertSame('desc', $result['query']['sort'][0]['direction']);
    }

    public function test_interprets_store_status(): void
    {
        $user = new User(['id' => 1]);

        $result = $this->interpreter->interpret($user, 'stores', 'open stores newest first');

        $this->assertArrayHasKey('store_status', $result['query']['filters'] ?? []);
        $this->assertSame('updated_at', $result['query']['sort'][0]['field']);
    }

    public function test_empty_query_returns_guidance(): void
    {
        $user = new User(['id' => 1]);

        $result = $this->interpreter->interpret($user, 'leads', '  ');

        $this->assertSame('none', $result['source']);
        $this->assertSame([], $result['query']);
    }
}
