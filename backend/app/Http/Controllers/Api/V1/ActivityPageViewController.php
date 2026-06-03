<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PageViewIngestRequest;
use App\Jobs\Activity\RecordNavigationJob;
use Illuminate\Http\JsonResponse;

final class ActivityPageViewController extends Controller
{
    public function store(PageViewIngestRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        RecordNavigationJob::dispatch($user->id, $request->paths());

        return response()->json(['accepted' => true], 202);
    }
}
