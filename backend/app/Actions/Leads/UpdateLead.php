<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Services\Leads\LeadApplicationStatusService;

final class UpdateLead
{
    public function __construct(
        private readonly LeadApplicationStatusService $applicationStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Lead $lead, array $attributes): Lead
    {
        $attributes = $this->applicationStatus->applyBeforeSave($lead, $attributes);

        $lead->update(collect($attributes)->only([
            'title',
            'owner_user_id',
            'organization_id',
            'area_id',
            'interest_region_id',
            'lead_status',
            'lead_stage',
            'lead_fdd_status',
            'lead_temp',
            'lead_source',
            'pipeline_phase',
            'likelihood_to_close',
            'disclosed_at',
            'nda_signed_at',
            'fdd_signed_at',
            'waiting_period_ends_at',
            'eligible_for_drip',
            'form_data',
        ])->filter(fn ($value) => $value !== null)->all());

        return $lead->fresh();
    }
}
