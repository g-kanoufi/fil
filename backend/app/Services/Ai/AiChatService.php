<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiMessage;
use App\Models\AiThread;
use Illuminate\Support\Facades\Http;

final class AiChatService
{
    /**
     * @return array{user: AiMessage, assistant: AiMessage}
     */
    public function sendMessage(AiThread $thread, string $content): array
    {
        $userMessage = AiMessage::query()->create([
            'ai_thread_id' => $thread->id,
            'role' => 'user',
            'content' => $content,
            'created_at' => now(),
        ]);

        $reply = $this->resolveAssistantReply($thread, $content);

        $assistantMessage = AiMessage::query()->create([
            'ai_thread_id' => $thread->id,
            'role' => 'assistant',
            'content' => $reply,
            'created_at' => now(),
        ]);

        $thread->touch();

        return [
            'user' => $userMessage,
            'assistant' => $assistantMessage,
        ];
    }

    private function resolveAssistantReply(AiThread $thread, string $content): string
    {
        $serviceUrl = config('fil.ai_service_url');

        if (is_string($serviceUrl) && $serviceUrl !== '') {
            $response = Http::timeout(30)->post(rtrim($serviceUrl, '/').'/chat', [
                'thread_id' => $thread->external_thread_id ?? $thread->id,
                'message' => $content,
                'lead_id' => $thread->lead_id,
            ]);

            if ($response->successful()) {
                return (string) ($response->json('reply') ?? $response->json('content') ?? $response->body());
            }
        }

        return 'Assistant is not configured yet. Your message was saved: '.mb_substr($content, 0, 120);
    }
}
