<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Communications\RecordInboundCommunication;
use App\Http\Controllers\Controller;
use App\Models\Communication;
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
    ): Response {
        if (! $verifier->verify($request)) {
            return response('Invalid signature', 403);
        }

        $from = (string) $request->input('From', '');
        $body = (string) $request->input('Body', '');

        if ($body !== '') {
            $record->handle(
                type: 'sms',
                message: $body,
                match: $resolver->resolveByPhone($from),
                provider: 'twilio',
                externalMessageId: (string) $request->input('MessageSid', '') ?: null,
                senderLabel: $from,
                meta: $request->only(['From', 'To', 'MessageSid']),
            );
        }

        return response('', 204);
    }

    public function status(Request $request, TwilioSignatureVerifier $verifier): Response
    {
        if (! $verifier->verify($request)) {
            return response('Invalid signature', 403);
        }

        $messageSid = (string) $request->input('MessageSid', '');
        $status = (string) $request->input('MessageStatus', '');

        if ($messageSid !== '') {
            Communication::query()
                ->where('external_message_id', $messageSid)
                ->update([
                    'status' => $status !== '' ? $status : 'updated',
                    'meta->delivery_status' => $status,
                ]);
        }

        return response('', 204);
    }
}
