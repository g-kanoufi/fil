<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\NotificationRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationRule */
final class NotificationRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hash' => $this->hash,
            'title' => $this->title,
            'trigger_slug' => $this->trigger_slug,
            'normalized_trigger_slug' => $this->normalizedTriggerSlug(),
            'enabled' => $this->enabled,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'body_html' => $this->body_html,
            'recipients' => $this->recipientTokens(),
            'conditionals' => $this->conditionalRules(),
            'schedule' => $this->effectiveSchedule(),
            'is_scheduled' => $this->isScheduledTrigger(),
            'profile_roles' => $this->profile_roles,
            'deliveries_count' => $this->whenCounted('deliveries'),
            'updated_at' => $this->updated_at,
        ];
    }
}
