<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PageViewIngestRequest;
use App\Jobs\Activity\RecordNavigationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

final class ActivityPageViewController extends Controller
{
    public function store(PageViewIngestRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if (! Schema::hasTable('activity_navigation')) {
            return response()->json([
                'error' => 'activity_navigation table missing — run php artisan migrate',
            ], 503);
        }

        // Sync upsert — fast and works without a queue worker (navigation is debounced client-side).
        RecordNavigationJob::dispatchSync($user->id, $request->paths());

        return response()->json(['accepted' => true], 202);
    }
}
