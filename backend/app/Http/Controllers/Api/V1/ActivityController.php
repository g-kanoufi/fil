<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Activity\ActivityFeedService;
use App\Services\Activity\ActivitySubjectAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ActivityController extends Controller
{
    public function index(Request $request, ActivityFeedService $feed): JsonResponse
    {
        $this->authorizeStaff($request);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $result = $feed->globalFeed(
            user: $user,
            limit: (int) $request->integer('limit', 25),
            cursor: $request->string('cursor')->toString() ?: null,
            days: (int) $request->integer('days', (int) config('fil-activity.default_feed_days', 30)),
            actorUserId: $request->filled('actor_user_id') ? (int) $request->integer('actor_user_id') : null,
            category: $request->string('category')->toString() ?: null,
        );

        return response()->json([
            'data' => $result['items'],
            'meta' => [
                'next_cursor' => $result['next_cursor'],
                'has_more' => $result['has_more'],
            ],
        ]);
    }

    public function forSubject(
        Request $request,
        string $type,
        int $id,
        ActivityFeedService $feed,
        ActivitySubjectAuthorizer $authorizer,
    ): JsonResponse {
        $this->authorizeStaff($request);
        $authorizer->authorize($type, $id);

        $items = $feed->subjectTimeline(
            subjectType: $type,
            subjectId: $id,
            limit: (int) $request->integer('limit', 25),
        );

        return response()->json(['data' => $items]);
    }

    private function authorizeStaff(Request $request): void
    {
        abort_unless($request->user()?->can('app.access') ?? false, 403);
    }
}
