<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Communications\RecordInboundCommunication;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\Activity\ActivityRecorder;
use App\Services\Communications\CommunicationWebhookService;
use App\Services\Communications\InboundCommunicationResolver;
use App\Services\Communications\TwilioSignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class TwilioWebhookController extends Controller
{
    public function inbound(
        Request $request,
        TwilioSignatureVerifier $verifier,
        InboundCommunicationResolver $resolver,
        RecordInboundCommunication $record,
        ActivityRecorder $activity,
    ): Response {
        if (! $verifier->verify($request)) {
            return response('Invalid signature', 403);
        }

        $from = (string) $request->input('From', '');
        $body = (string) $request->input('Body', '');

        if ($body !== '') {
            $communication = $record->handle(
                type: 'sms',
                message: $body,
                match: $resolver->resolveByPhone($from),
                provider: 'twilio',
                externalMessageId: (string) $request->input('MessageSid', '') ?: null,
                senderLabel: $from,
                meta: $request->only(['From', 'To', 'MessageSid']),
            );

            $subject = $communication->lead_id !== null
                ? Lead::query()->find($communication->lead_id)
                : null;

            $activity->record(
                category: 'comm',
                action: 'delivered',
                summary: sprintf('Inbound SMS from %s', $from),
                actor: null,
                subject: $subject,
                object: $communication,
                payload: ['communication_id' => $communication->id, 'direction' => 'inbound'],
                source: 'twilio_webhook',
            );
        }

        return response('', 204);
    }

    public function status(
        Request $request,
        TwilioSignatureVerifier $verifier,
        CommunicationWebhookService $webhooks,
    ): Response {
        if (! $verifier->verify($request)) {
            return response('Invalid signature', 403);
        }

        $messageSid = (string) $request->input('MessageSid', '');
        $status = (string) $request->input('MessageStatus', '');

        if ($messageSid !== '') {
            $webhooks->handleTwilioStatus(
                $messageSid,
                $status,
                $request->only(['MessageSid', 'MessageStatus', 'ErrorCode', 'ErrorMessage', 'To', 'From']),
            );
        }

        return response('', 204);
    }
}
