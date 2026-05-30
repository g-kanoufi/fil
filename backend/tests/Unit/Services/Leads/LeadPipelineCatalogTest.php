<?php

declare(strict_types=1);
use App\Models\Lead;
use App\Services\Leads\LeadPipelineCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->catalog = app(LeadPipelineCatalog::class);
});
test('maps legacy progress to streamlined phase', function () {
    expect($this->catalog->normalizeLegacyProgress(5))->toBe(5);
    expect($this->catalog->normalizeLegacyProgress(10))->toBe(7);
    expect($this->catalog->normalizeLegacyProgress(13))->toBe(10);
});
test('resolves numeric lead status label', function () {
    $lead = Lead::factory()->make([
        'lead_status' => '6',
        'lead_fdd_status' => null,
    ]);

    expect($this->catalog->applicationStatusLabel($lead))->toBe('Spoke with Prospect');
});
test('resolves fdd status label', function () {
    $lead = Lead::factory()->make([
        'lead_status' => null,
        'lead_fdd_status' => 'disclosed',
    ]);

    expect($this->catalog->applicationStatusLabel($lead))->toBe('FDD Sent');
});
test('infers pipeline phase from status signals', function () {
    $lead = Lead::factory()->make([
        'pipeline_phase' => 1,
        'lead_fdd_status' => 'waiting_period',
        'lead_status' => null,
    ]);

    expect($this->catalog->resolvePipelinePhase($lead))->toBe(8);
});
test('presentation returns labels not raw numbers', function () {
    $lead = Lead::factory()->make([
        'pipeline_phase' => 5,
        'lead_status' => '6',
        'lead_fdd_status' => 'disclosed',
    ]);

    $presentation = $this->catalog->presentation($lead);

    expect($presentation['pipeline_phase'])->toBe(5);
    expect($presentation['pipeline_phase_label'])->toBe('FDD Disclosed');
    expect($presentation['application_status_label'])->toBe('FDD Sent');
});
test('merges closed status into closed phase', function () {
    $lead = Lead::factory()->make([
        'pipeline_phase' => 1,
        'lead_status' => '9',
        'lead_fdd_status' => 'inactive',
    ]);

    expect($this->catalog->resolvePipelinePhase($lead))->toBe(99);
});
