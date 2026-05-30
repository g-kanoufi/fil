<?php

declare(strict_types=1);

namespace App\Actions\Fdd;

use App\Models\Lead;
use App\Models\User;
use App\Services\Fdd\FddLeadResolver;

final class BulkSendFddDeliveries
{
    public function __construct(
        private readonly FddLeadResolver $resolver,
        private readonly SendFddDelivery $sendFdd,
    ) {}

    /**
     * @param  list<int>  $leadIds
     * @return array{sent: list<array<string, mixed>>, skipped: list<array<string, mixed>>}
     */
    public function handle(array $leadIds, string $type, User $sender): array
    {
        $sent = [];
        $skipped = [];

        foreach ($leadIds as $leadId) {
            $lead = Lead::query()->with('prospect')->find($leadId);

            if ($lead === null) {
                $skipped[] = ['lead_id' => $leadId, 'reason' => 'Lead not found'];

                continue;
            }

            $fdd = $this->resolver->resolveForLead($lead, $type);

            if ($fdd === null) {
                $skipped[] = [
                    'lead_id' => $lead->id,
                    'lead_title' => $lead->title,
                    'reason' => 'No '.$type.' FDD available for this lead\'s area',
                ];

                continue;
            }

            $delivery = $this->sendFdd->handle($fdd, $lead, $sender);
            $sent[] = [
                'lead_id' => $lead->id,
                'lead_title' => $lead->title,
                'delivery_id' => $delivery->id,
                'fdd_id' => $fdd->id,
                'fdd_title' => $fdd->title,
            ];
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }
}
