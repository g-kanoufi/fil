<?php

declare(strict_types=1);

namespace App\Services\Drips;

use App\Models\DripCampaign;
use App\Models\DripStep;
use Illuminate\Support\Facades\DB;

final class DripCampaignStepSyncService
{
    /**
     * @param  list<array<string, mixed>>  $steps
     */
    public function sync(DripCampaign $campaign, array $steps): void
    {
        DB::transaction(function () use ($campaign, $steps): void {
            $keepIds = [];

            foreach (array_values($steps) as $index => $stepData) {
                $payload = [
                    'sort_order' => (int) ($stepData['sort_order'] ?? ($index + 1)),
                    'delay_days' => (int) ($stepData['delay_days'] ?? 0),
                    'delay_hours' => (int) ($stepData['delay_hours'] ?? 0),
                    'channel' => (string) ($stepData['channel'] ?? 'email'),
                    'subject' => $stepData['subject'] ?? null,
                    'body_template' => (string) ($stepData['body_template'] ?? ''),
                    'status' => (string) ($stepData['status'] ?? 'active'),
                ];

                if (isset($stepData['id'])) {
                    $step = DripStep::query()
                        ->where('drip_campaign_id', $campaign->id)
                        ->find((int) $stepData['id']);

                    if ($step !== null) {
                        $step->update($payload);
                        $keepIds[] = $step->id;

                        continue;
                    }
                }

                $created = DripStep::query()->create([
                    'drip_campaign_id' => $campaign->id,
                    ...$payload,
                ]);

                $keepIds[] = $created->id;
            }

            DripStep::query()
                ->where('drip_campaign_id', $campaign->id)
                ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
                ->when($keepIds === [], fn ($query) => $query)
                ->delete();
        });
    }
}
