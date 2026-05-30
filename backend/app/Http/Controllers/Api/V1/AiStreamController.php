<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendAiMessageRequest;
use App\Models\AiThread;
use App\Services\Ai\AiChatService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AiStreamController extends Controller
{
    public function stream(
        SendAiMessageRequest $request,
        AiThread $aiThread,
        AiChatService $chatService,
    ): StreamedResponse {
        $this->authorize('view', $aiThread);

        $content = $request->validated('content');

        return response()->stream(function () use ($chatService, $aiThread, $content): void {
            $result = $chatService->sendMessage($aiThread, $content);
            $reply = $result['assistant']->content;

            foreach (preg_split('/(\s+)/', $reply, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$reply] as $chunk) {
                if ($chunk === '') {
                    continue;
                }

                echo 'data: '.json_encode(['token' => $chunk], JSON_THROW_ON_ERROR)."\n\n";
                ob_flush();
                flush();
            }

            echo 'data: '.json_encode(['done' => true, 'message_id' => $result['assistant']->id], JSON_THROW_ON_ERROR)."\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
