<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Leads;

use App\Models\Lead;
use App\Services\Leads\LeadPipelineCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LeadPipelineCatalogTest extends TestCase
{
    use RefreshDatabase;

    private LeadPipelineCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = app(LeadPipelineCatalog::class);
    }

    public function test_maps_legacy_progress_to_streamlined_phase(): void
    {
        $this->assertSame(5, $this->catalog->normalizeLegacyProgress(5));
        $this->assertSame(7, $this->catalog->normalizeLegacyProgress(10));
        $this->assertSame(10, $this->catalog->normalizeLegacyProgress(13));
    }

    public function test_resolves_numeric_lead_status_label(): void
    {
        $lead = Lead::factory()->make([
            'lead_status' => '6',
            'lead_fdd_status' => null,
        ]);

        $this->assertSame('Spoke with Prospect', $this->catalog->applicationStatusLabel($lead));
    }

    public function test_resolves_fdd_status_label(): void
    {
        $lead = Lead::factory()->make([
            'lead_status' => null,
            'lead_fdd_status' => 'disclosed',
        ]);

        $this->assertSame('FDD Sent', $this->catalog->applicationStatusLabel($lead));
    }

    public function test_infers_pipeline_phase_from_status_signals(): void
    {
        $lead = Lead::factory()->make([
            'pipeline_phase' => 1,
            'lead_fdd_status' => 'waiting_period',
            'lead_status' => null,
        ]);

        $this->assertSame(8, $this->catalog->resolvePipelinePhase($lead));
    }

    public function test_presentation_returns_labels_not_raw_numbers(): void
    {
        $lead = Lead::factory()->make([
            'pipeline_phase' => 5,
            'lead_status' => '6',
            'lead_fdd_status' => 'disclosed',
        ]);

        $presentation = $this->catalog->presentation($lead);

        $this->assertSame(5, $presentation['pipeline_phase']);
        $this->assertSame('FDD Disclosed', $presentation['pipeline_phase_label']);
        $this->assertSame('FDD Sent', $presentation['application_status_label']);
    }

    public function test_merges_closed_status_into_closed_phase(): void
    {
        $lead = Lead::factory()->make([
            'pipeline_phase' => 1,
            'lead_status' => '9',
            'lead_fdd_status' => 'inactive',
        ]);

        $this->assertSame(99, $this->catalog->resolvePipelinePhase($lead));
    }
}
