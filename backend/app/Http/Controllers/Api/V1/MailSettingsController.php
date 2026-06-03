<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Settings;
use App\Http\Controllers\Controller;
use App\Services\Communications\CommunicationProviderReadiness;
use App\Services\Mail\MailgunVerificationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MailSettingsController extends Controller
{
    public function show(
        MailgunVerificationService $mailgun,
        CommunicationProviderReadiness $providerReadiness,
    ): JsonResponse {
        $this->authorize('manage', Settings::class);

        return ApiResponse::payload([
            ...$mailgun->status(),
            'communication_webhooks' => $providerReadiness->status(),
        ]);
    }

    public function verify(MailgunVerificationService $mailgun): JsonResponse
    {
        $this->authorize('manage', Settings::class);

        return ApiResponse::payload([
            'mailgun' => $mailgun->verifyDomain(),
            'status' => $mailgun->status(),
        ]);
    }

    public function sendTest(Request $request, MailgunVerificationService $mailgun): JsonResponse
    {
        $this->authorize('manage', Settings::class);

        $validated = $request->validate([
            'recipient' => ['nullable', 'email', 'max:255'],
        ]);

        $recipient = (string) ($validated['recipient'] ?? $request->user()?->email ?? '');

        $result = $mailgun->sendTestEmail($recipient);

        if (! $result['sent']) {
            return ApiResponse::message(
                (string) ($result['error'] ?? 'Failed to send test email'),
                422,
            );
        }

        return ApiResponse::payload($result);
    }
}
