<?php

declare(strict_types=1);

namespace App\Services\ESign;

use App\Actions\Fdd\RecordFddSignature;
use App\Contracts\ESign\ESignClient;
use App\Models\FddDelivery;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;

final class ESignService
{
    public function client(): ESignClient
    {
        $driver = (string) config('fil-platform.esign.driver', 'local');

        return match ($driver) {
            'local' => App::make(LocalESignClient::class),
            'sandbox' => App::make(SandboxESignClient::class),
            'dropbox_sign' => App::make(DropboxSignESignClient::class),
            default => throw new InvalidArgumentException("Unknown e-sign driver: {$driver}"),
        };
    }

    /**
     * @return array{mode: 'local'|'embedded', signing_url?: string, vendor_reference?: string, driver: string}
     */
    public function beginProspectSigning(FddDelivery $delivery, User $signer): array
    {
        $pending = $this->pendingSignature($delivery, $signer);
        $client = $this->client();
        $session = $client->beginSigning($delivery, $signer, $pending);

        return [...$session, 'driver' => $client->driver()];
    }

    public function completeLocalSign(FddDelivery $delivery, User $signer, array $payload): Signature
    {
        return app(RecordFddSignature::class)->handle($delivery, $signer, $payload);
    }

    public function completeVendorSign(Signature $pending, User $signer, ?string $signedName = null): Signature
    {
        $delivery = $pending->fddDelivery ?? FddDelivery::query()->findOrFail($pending->fdd_delivery_id);
        $name = $signedName ?: $signer->name;

        return app(RecordFddSignature::class)->handle($delivery, $signer, [
            'signed_name' => $name,
            'agree' => true,
        ]);
    }

    private function pendingSignature(FddDelivery $delivery, User $signer): Signature
    {
        $existing = Signature::query()
            ->where('fdd_delivery_id', $delivery->id)
            ->where('status', 'pending')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $documentId = $delivery->meta['document_id'] ?? null;

        return Signature::query()->create([
            'document_id' => is_numeric($documentId) ? (int) $documentId : null,
            'lead_id' => $delivery->lead_id,
            'fdd_delivery_id' => $delivery->id,
            'signer_user_id' => $signer->id,
            'status' => 'pending',
        ]);
    }
}
