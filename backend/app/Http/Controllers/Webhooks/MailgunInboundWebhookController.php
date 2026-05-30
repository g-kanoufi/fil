<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Communications\RecordInboundCommunication;
use App\Http\Controllers\Controller;
use App\Services\Communications\InboundCommunicationResolver;
use App\Services\Mail\MailgunSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MailgunInboundWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MailgunSignatureVerifier $verifier,
        InboundCommunicationResolver $resolver,
        RecordInboundCommunication $record,
    ): JsonResponse {
        if (! $verifier->verify($request)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $from = (string) ($request->input('sender') ?: $request->input('From') ?: $request->input('from', ''));
        $body = (string) ($request->input('stripped-text') ?: $request->input('body-plain') ?: $request->input('body-html', ''));
        $subject = (string) $request->input('subject', '');
        $messageId = trim((string) ($request->input('Message-Id') ?: $request->input('message-id', '')), "<> \t");

        if ($body === '' && $subject === '') {
            return response()->json(['received' => true, 'skipped' => 'empty']);
        }

        $match = $resolver->resolveByEmail($from);
        $message = $body !== '' ? $body : $subject;

        $record->handle(
            type: 'email',
            message: $message,
            match: $match,
            provider: 'mailgun',
            externalMessageId: $messageId !== '' ? $messageId : null,
            senderLabel: $from,
            meta: [
                'subject' => $subject,
                'from' => $from,
                'to' => $request->input('recipient') ?? $request->input('To'),
            ],
        );

        return response()->json(['received' => true]);
    }
}
