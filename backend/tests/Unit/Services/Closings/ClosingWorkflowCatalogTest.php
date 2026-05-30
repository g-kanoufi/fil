<?php

namespace Tests\Unit\Services\Closings;

use App\Services\Closings\ClosingWorkflowCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClosingWorkflowCatalogTest extends TestCase
{
    #[Test]
    public function it_allows_configured_status_transitions(): void
    {
        $catalog = app(ClosingWorkflowCatalog::class);

        $this->assertTrue($catalog->canTransition('pending', 'scheduled'));
        $this->assertFalse($catalog->canTransition('pending', 'completed'));
        $this->assertSame('Scheduled', $catalog->label('scheduled'));
    }

    #[Test]
    public function it_normalizes_fee_lines(): void
    {
        $catalog = app(ClosingWorkflowCatalog::class);

        $lines = $catalog->normalizeFeeLines([
            ['label' => ' Franchise fee ', 'amount_cents' => 10000],
            ['label' => '', 'amount_cents' => 500],
            ['label' => 'Ignored', 'amount_cents' => 'n/a'],
        ]);

        $this->assertSame([
            ['label' => 'Franchise fee', 'amount_cents' => 10000],
        ], $lines);
    }
}
