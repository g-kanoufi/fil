<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Str;

final class CreateLead
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, ?User $actor = null): Lead
    {
        $title = (string) ($attributes['title'] ?? 'New Application');

        return Lead::query()->create([
            'title' => $title,
            'slug' => $attributes['slug'] ?? Str::slug($title).'-'.Str::lower(Str::random(6)),
            'prospect_user_id' => $attributes['prospect_user_id'] ?? null,
            'owner_user_id' => $attributes['owner_user_id'] ?? $actor?->id,
            'organization_id' => $attributes['organization_id'] ?? null,
            'area_id' => $attributes['area_id'] ?? null,
            'interest_region_id' => $attributes['interest_region_id'] ?? null,
            'pipeline_phase' => $attributes['pipeline_phase'] ?? 1,
            'lead_status' => $attributes['lead_status'] ?? 'new_lead',
            'lead_stage' => $attributes['lead_stage'] ?? '1',
            'lead_fdd_status' => $attributes['lead_fdd_status'] ?? ($attributes['lead_status'] ?? 'new_lead'),
            'lead_temp' => $attributes['lead_temp'] ?? null,
            'lead_source' => $attributes['lead_source'] ?? ($attributes['source'] ?? 'widget'),
            'likelihood_to_close' => $attributes['likelihood_to_close'] ?? null,
            'status' => $attributes['status'] ?? 'active',
        ]);
    }
}
