<?php

declare(strict_types=1);

namespace App\Actions\Fdd;

use App\Models\FddDelivery;
use App\Models\Lead;
use App\Models\Signature;
use App\Models\User;
use App\Services\Leads\LeadPipelineService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordFddSignature
{
    public function __construct(
        private readonly LeadPipelineService $pipelineService,
    ) {}

    /**
     * @param  array{signed_name: string, agree?: bool}  $payload
     */
    public function handle(FddDelivery $delivery, User $signer, array $payload): Signature
    {
        if ($delivery->status === 'signed') {
            throw ValidationException::withMessages(['delivery' => 'This FDD delivery is already signed.']);
        }

        $signedName = trim($payload['signed_name']);

        if ($signedName === '') {
            throw ValidationException::withMessages(['signed_name' => 'Signature name is required.']);
        }

        if (($payload['agree'] ?? false) !== true) {
            throw ValidationException::withMessages(['agree' => 'You must agree to sign the FDD.']);
        }

        return DB::transaction(function () use ($delivery, $signer, $signedName): Signature {
            $documentId = $delivery->meta['document_id'] ?? null;

            $signature = Signature::query()
                ->where('fdd_delivery_id', $delivery->id)
                ->where('status', 'pending')
                ->first();

            if ($signature !== null) {
                $signature->update([
                    'signer_user_id' => $signer->id,
                    'signed_at' => now(),
                    'status' => 'signed',
                    'signature_data' => [
                        'signed_name' => $signedName,
                        'method' => 'electronic',
                    ],
                    'ip_address' => request()->ip(),
                ]);
            } else {
                $signature = Signature::query()->create([
                    'document_id' => is_numeric($documentId) ? (int) $documentId : null,
                    'lead_id' => $delivery->lead_id,
                    'fdd_delivery_id' => $delivery->id,
                    'signer_user_id' => $signer->id,
                    'signed_at' => now(),
                    'status' => 'signed',
                    'signature_data' => [
                        'signed_name' => $signedName,
                        'method' => 'electronic',
                    ],
                    'ip_address' => request()->ip(),
                ]);
            }

            $delivery->update(['status' => 'signed']);

            if ($delivery->lead_id !== null) {
                $lead = Lead::query()->find($delivery->lead_id);

                if ($lead !== null) {
                    $lead->update([
                        'fdd_signed_at' => $lead->fdd_signed_at ?? now(),
                        'waiting_period_ends_at' => now()->addDays(14),
                        'lead_fdd_status' => 'waiting_period',
                    ]);

                    if ((int) $lead->pipeline_phase < 8) {
                        $this->pipelineService->transition($lead->fresh(), 8, $signer, [
                            'source' => 'fdd_signature',
                            'fdd_delivery_id' => $delivery->id,
                        ]);
                    }

                    app(NotificationDispatcher::class)
                        ->dispatch('application.fdd_signed', $lead->fresh(), [
                            'fdd_delivery_id' => $delivery->id,
                            'changed_fields' => ['fdd_signed_at', 'lead_fdd_status'],
                        ], $signer);
                }
            }

            return $signature->fresh(['fddDelivery', 'signer', 'lead']);
        });
    }
}
