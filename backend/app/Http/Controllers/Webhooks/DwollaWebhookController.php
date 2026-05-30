<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\AchCustomer;
use App\Models\AchTransfer;
use App\Models\WebhookEvent;
use App\Services\Ach\DwollaSignatureVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DwollaWebhookController extends Controller
{
    public function __invoke(Request $request, DwollaSignatureVerifier $verifier): JsonResponse
    {
        if (! $verifier->verify($request)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $eventId = (string) ($payload['id'] ?? '');

        if ($eventId !== '') {
            try {
                WebhookEvent::query()->create([
                    'provider' => 'dwolla',
                    'external_event_id' => $eventId,
                ]);
            } catch (QueryException) {
                return response()->json(['error' => 'Duplicate webhook event'], 409);
            }
        }

        $topic = (string) ($payload['topic'] ?? $request->header('X-Dwolla-Topic', ''));
        $resourceId = (string) ($payload['resourceId'] ?? '');

        if ($resourceId !== '' && str_contains($topic, 'transfer')) {
            AchTransfer::query()
                ->where('external_transfer_id', $resourceId)
                ->update([
                    'provider_status' => $this->mapTransferTopicToStatus($topic),
                ]);
        }

        if ($resourceId !== '' && str_contains($topic, 'customer')) {
            AchCustomer::query()
                ->where('external_customer_id', $resourceId)
                ->update([
                    'status' => $this->mapCustomerTopicToStatus($topic),
                ]);
        }

        return response()->json(['received' => true]);
    }

    private function mapTransferTopicToStatus(string $topic): string
    {
        return match (true) {
            str_contains($topic, 'completed') => 'processed',
            str_contains($topic, 'failed') => 'failed',
            str_contains($topic, 'cancelled') => 'cancelled',
            default => 'pending',
        };
    }

    private function mapCustomerTopicToStatus(string $topic): string
    {
        return match (true) {
            str_contains($topic, 'verified') => 'verified',
            str_contains($topic, 'document') => 'document',
            str_contains($topic, 'suspended') => 'suspended',
            str_contains($topic, 'deactivated') => 'deactivated',
            str_contains($topic, 'created') => 'unverified',
            default => 'unverified',
        };
    }
}
