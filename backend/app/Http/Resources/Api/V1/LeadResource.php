<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Lead;
use App\Services\Fields\EntityFieldValueReader;
use App\Services\Leads\LeadPipelineCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lead */
final class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LeadPipelineCatalog $catalog */
        $catalog = app(LeadPipelineCatalog::class);
        $presentation = $catalog->presentation($this->resource);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'pipeline_phase' => $presentation['pipeline_phase'],
            'pipeline_phase_label' => $presentation['pipeline_phase_label'],
            'application_status' => $presentation['application_status'],
            'application_status_label' => $presentation['application_status_label'],
            'lead_status' => $catalog->normalizeStatusValue($this->lead_status),
            'lead_stage' => $this->lead_stage,
            'lead_fdd_status' => $catalog->normalizeStatusValue($this->lead_fdd_status),
            'lead_temp' => $this->lead_temp,
            'lead_source' => $this->lead_source,
            'likelihood_to_close' => $this->likelihood_to_close,
            'owner_user_id' => $this->owner_user_id,
            'prospect_user_id' => $this->prospect_user_id,
            'prospect' => $this->whenLoaded('prospect', fn (): array => [
                'id' => (int) $this->prospect?->id,
                'name' => (string) $this->prospect?->name,
                'email' => $this->prospect?->email,
                'phone' => $this->prospect?->phone,
            ]),
            'area_id' => $this->area_id,
            'organization_id' => $this->organization_id,
            'status' => $this->status,
            'fdd_signed_at' => $this->fdd_signed_at?->toIso8601String(),
            'disclosed_at' => $this->disclosed_at?->toIso8601String(),
            'phase_events' => LeadPhaseEventResource::collection($this->whenLoaded('phaseEvents')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'custom' => $this->when(
                $request->routeIs(
                    'api.v1.leads.show',
                    'api.v1.leads.update',
                    'api.v1.leads.store',
                    'api.v1.leads.convert',
                ),
                fn (): array => app(EntityFieldValueReader::class)->forEntity('lead', $this->id),
            ),
        ];
    }
}
