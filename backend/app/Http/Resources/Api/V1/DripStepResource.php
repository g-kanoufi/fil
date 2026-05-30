<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\DripStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DripStep */
final class DripStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'drip_campaign_id' => $this->drip_campaign_id,
            'sort_order' => $this->sort_order,
            'delay_days' => $this->delay_days,
            'delay_hours' => $this->delay_hours,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'body_template' => $this->body_template,
            'template_id' => $this->template_id,
            'conditions' => $this->conditions,
            'status' => $this->status,
        ];
    }
}
