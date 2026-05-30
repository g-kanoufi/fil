<?php

declare(strict_types=1);

namespace App\Actions\Fdd;

use App\Models\Fdd;
use App\Models\FddDelivery;
use App\Models\Lead;
use App\Models\Signature;
use App\Models\User;
use App\Services\Fdd\FddPdfService;
use App\Services\Leads\LeadPipelineService;
use Illuminate\Support\Facades\DB;
use App\Mail\FddDeliveryMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class SendFddDelivery
{
    public function __construct(
        private readonly FddPdfService $pdfService,
        private readonly LeadPipelineService $pipelineService,
    ) {}

    public function handle(Fdd $fdd, Lead $lead, User $sender, ?User $recipient = null): FddDelivery
    {
        $recipient ??= $lead->prospect;
        $document = $this->pdfService->ensureDocument($fdd);

        return DB::transaction(function () use ($fdd, $lead, $sender, $recipient, $document): FddDelivery {
            $signToken = Str::random(48);

            $delivery = FddDelivery::query()->create([
                'fdd_id' => $fdd->id,
                'lead_id' => $lead->id,
                'recipient_user_id' => $recipient?->id,
                'sent_at' => now(),
                'status' => 'sent',
                'delivery_method' => 'email',
                'meta' => [
                    'sent_by_user_id' => $sender->id,
                    'document_id' => $document->id,
                    'sign_token' => $signToken,
                ],
            ]);

            Signature::query()->create([
                'document_id' => $document->id,
                'lead_id' => $lead->id,
                'fdd_delivery_id' => $delivery->id,
                'signer_user_id' => $recipient?->id,
                'status' => 'pending',
            ]);

            if ($recipient !== null && filled($recipient->email)) {
                Mail::to($recipient->email)->send(new FddDeliveryMail(
                    fddTitle: (string) $fdd->title,
                    documentId: (string) $document->id,
                    deliveryId: (int) $delivery->id,
                ));
            }

            if ($lead->disclosed_at === null) {
                $lead->update(['disclosed_at' => now()]);
            }

            if ($lead->lead_fdd_status === null || $lead->lead_fdd_status === 'active') {
                $lead->update(['lead_fdd_status' => 'disclosed']);
            }

            if ((int) $lead->pipeline_phase < 5) {
                $this->pipelineService->transition($lead->fresh(), 5, $sender, [
                    'source' => 'fdd_delivery',
                    'fdd_delivery_id' => $delivery->id,
                ]);
            }

            app(\App\Services\Notifications\NotificationDispatcher::class)
                ->dispatch('application.send_prospect_fdd', $lead->fresh(), [
                    'fdd_delivery_id' => $delivery->id,
                    'fdd_id' => $fdd->id,
                ], $sender);

            return $delivery->fresh(['fdd', 'lead', 'recipient']);
        });
    }
}
