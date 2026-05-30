<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateAiThreadRequest;
use App\Http\Requests\Api\V1\SendAiMessageRequest;
use App\Http\Resources\Api\V1\AiMessageResource;
use App\Http\Resources\Api\V1\AiThreadResource;
use App\Models\AiThread;
use App\Models\Lead;
use App\Services\Ai\AiChatService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AiThreadController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AiThread::class);

        $threads = AiThread::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return ApiResponse::collection(AiThreadResource::collection($threads));
    }

    public function store(CreateAiThreadRequest $request): JsonResponse
    {
        $leadId = $request->validated('lead_id');

        if ($leadId !== null) {
            $lead = Lead::query()->findOrFail((int) $leadId);
            $this->authorize('view', $lead);
        }

        $thread = AiThread::query()->create([
            'user_id' => $request->user()->id,
            'lead_id' => $request->validated('lead_id'),
            'title' => $request->validated('title') ?? 'New conversation',
            'status' => 'active',
        ]);

        return ApiResponse::resource(new AiThreadResource($thread), 201);
    }

    public function show(AiThread $aiThread): JsonResponse
    {
        $this->authorize('view', $aiThread);

        $aiThread->load('messages');

        return ApiResponse::resource(new AiThreadResource($aiThread));
    }

    public function sendMessage(
        SendAiMessageRequest $request,
        AiThread $aiThread,
        AiChatService $chatService,
    ): JsonResponse {
        $result = $chatService->sendMessage($aiThread, $request->validated('content'));

        return response()->json([
            'data' => [
                'user_message' => new AiMessageResource($result['user']),
                'assistant_message' => new AiMessageResource($result['assistant']),
            ],
        ]);
    }
}
