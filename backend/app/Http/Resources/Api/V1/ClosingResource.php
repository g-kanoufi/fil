<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Closing;
use App\Services\Closings\ClosingWorkflowCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Closing */
final class ClosingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $workflow = app(ClosingWorkflowCatalog::class);
        $feeLines = $workflow->feeLinesFromExtras($this->extras);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'lead_id' => $this->lead_id,
            'lead_title' => $this->whenLoaded('lead', fn () => $this->lead?->title),
            'store_id' => $this->store_id,
            'store_title' => $this->whenLoaded('store', fn () => $this->store?->title),
            'area_id' => $this->area_id,
            'area_title' => $this->whenLoaded('area', fn () => $this->area?->name),
            'closing_date' => $this->closing_date?->toDateString(),
            'status' => $this->status,
            'status_label' => $workflow->label((string) $this->status),
            'fee_lines' => $feeLines,
            'fee_total_cents' => array_sum(array_column($feeLines, 'amount_cents')),
            'allowed_status_transitions' => $workflow->transitionOptions((string) $this->status),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
